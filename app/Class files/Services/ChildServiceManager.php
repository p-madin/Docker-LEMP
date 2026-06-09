<?php
class ChildServiceManager {

    private function getDockerCmdPrefix(): string {
        $dockerHost = getenv('DOCKER_HOST');
        $cmdPrefix = "";

        if ($dockerHost && strpos($dockerHost, 'tcp://') === 0) {
            $parsed = parse_url($dockerHost);
            $host = isset($parsed['host']) ? $parsed['host'] : '';
            $port = isset($parsed['port']) ? $parsed['port'] : 2375;
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 0.5);
            if ($connection) {
                fclose($connection);
            } else {
                $dockerHost = null;
            }
        }

        if (!$dockerHost && file_exists('/var/run/docker.sock')) {
            if (!is_writable('/var/run/docker.sock')) {
                $cmdPrefix = "sudo ";
            }
        } elseif ($dockerHost) {
            $cmdPrefix = "DOCKER_HOST=" . escapeshellarg($dockerHost) . " ";
        }

        return $cmdPrefix;
    }

    public function start(string $tenant): array {
        global $db, $dialect;
        $tenantDir = "/tenants/{$tenant}";

        if (!is_dir($tenantDir)) {
            mkdir($tenantDir, 0755, true);
        }

        // 1 — Pull admin user info and generate SQL script
        $sqlContent = "-- No admin user mapped for this tenant.\n";
        $qb = new \QueryBuilder($dialect);
        $service = $qb->table('absChildServices')->select(['csAdminFK'])->where('csName', '=', $tenant)->getFetch($db);
        if ($service && !empty($service['csAdminFK'])) {
            $qb2 = new \QueryBuilder($dialect);
            $adminUser = $qb2->table('appUsers')->where('auPK', '=', $service['csAdminFK'])->getFetch($db);
            if ($adminUser) {
                // Ensure values are properly escaped for SQL (this uses basic string replacement, PDO would be better but we're generating a static script)
                $name = str_replace("'", "''", $adminUser['name']);
                $age = (int)$adminUser['age'];
                $city = str_replace("'", "''", $adminUser['city']);
                $username = str_replace("'", "''", $adminUser['username']);
                $password = str_replace("'", "''", $adminUser['password']);
                $email = str_replace("'", "''", $adminUser['email']);
                
                $sqlContent = "use stackDB;\n\nINSERT INTO appUsers (name, age, city, username, password, email, verified) VALUES ('{$name}', {$age}, '{$city}', '{$username}', '{$password}', '{$email}', true);\n";
            }
        }
        file_put_contents("{$tenantDir}/99_tenant_admin.sql", $sqlContent);

        // 2 — write the tenant compose file from template
        $compose = $this->generateComposeFile($tenant);
        file_put_contents("{$tenantDir}/compose.yaml", $compose);

        // 3 — write the tenant env file
        $env = $this->generateEnvFile($tenant);
        file_put_contents("{$tenantDir}/.env", $env);

        $cmdPrefix = $this->getDockerCmdPrefix();

        shell_exec("{$cmdPrefix}docker network create superhost-network 2>/dev/null || true");
        
        // 3 — bring the stack up
        $output = shell_exec("{$cmdPrefix}docker-compose -f {$tenantDir}/compose.yaml --env-file {$tenantDir}/.env up -d 2>&1");
        
        // wait for the compose to complete...
        sleep(3);
        error_log($output);

        // 4 — write nginx config and reload
        $nginxConf = $this->generateNginxConf($tenant);
        file_put_contents("/usr/local/nginx/conf.d/{$tenant}.conf", $nginxConf);
        $testOutput = shell_exec("sudo /usr/local/nginx/nginx -t 2>&1");

        if (strpos($testOutput, 'successful') !== false) {
            shell_exec("sudo /usr/local/nginx/nginx -s reload 2>&1");
        } else {
            error_log("Nginx config invalid for tenant {$tenant}: {$testOutput}");
        }

        return ['status' => 'success', 'output' => $output];
    }

    public function stop(string $tenant): array {
        $tenantDir = "/tenants/{$tenant}";
        $cmdPrefix = $this->getDockerCmdPrefix();

        if (file_exists("{$tenantDir}/compose.yaml")) {
            $output = shell_exec("{$cmdPrefix}docker-compose -f {$tenantDir}/compose.yaml --env-file {$tenantDir}/.env down 2>&1");
            return ['status' => 'success', 'output' => $output];
        }
        return ['status' => 'error', 'message' => 'Tenant configuration not found.'];
    }

    public function delete(string $tenant): array {
        $tenantDir = "/tenants/{$tenant}";
        $cmdPrefix = $this->getDockerCmdPrefix();

        $output = "";
        if (file_exists("{$tenantDir}/compose.yaml")) {
            $output = shell_exec("{$cmdPrefix}docker-compose -f {$tenantDir}/compose.yaml --env-file {$tenantDir}/.env down -v 2>&1");
        }
        
        // Remove nginx conf and reload
        $nginxConfPath = "/usr/local/nginx/conf.d/{$tenant}.conf";
        if (file_exists($nginxConfPath)) {
            unlink($nginxConfPath);
            shell_exec("sudo /usr/local/nginx/nginx -s reload 2>&1");
        }
        
        // Clean up tenant directory
        if (is_dir($tenantDir)) {
            shell_exec("rm -rf " . escapeshellarg($tenantDir));
        }

        return ['status' => 'success', 'output' => $output];
    }

    public function sync(string $tenant): array {
        $cmdPrefix = $this->getDockerCmdPrefix();
        // Get docker child current status
        $containerName = "{$tenant}-app-1";
        $statusOutput = shell_exec("{$cmdPrefix}docker inspect -f '{{.State.Status}}' " . escapeshellarg($containerName) . " 2>/dev/null");
        $status = trim($statusOutput);
        
        // cURL check
        $curlOutput = "";
        $httpCode = 0;
        if ($status === 'running') {
            $ch = curl_init("https://localhost/{$tenant}/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $curlOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        return [
            'docker_status' => $status, // e.g., 'running', 'exited', ''
            'http_code' => $httpCode,
            'is_active' => ($status === 'running' && $httpCode >= 200 && $httpCode < 400),
            'curl_output' => $curlOutput
        ];
    }

    private function generateComposeFile(string $tenant): string {
        global $scvRows;

        $templatePath = '/home/ubuntu/Workspace/compose.tenant.yaml';
        $template = file_exists($templatePath) ? file_get_contents($templatePath) : '';

        $tenantAppVolume = $scvRows['TENANT_APP_VOLUME'];
        $volumeSection = "";
        if ($tenantAppVolume) {
            $volumeSection = "    volumes:\n      - \"{$tenantAppVolume}\"";
        }
        $template = str_replace('#TENANT_APP_VOLUME_PLACEHOLDER#', $volumeSection, $template);

        return "name: {$tenant}\n\n" . $template;
    }

    private function generateEnvFile(string $tenant): string {
        global $scvRows;
        $dbUser = 'user_' . preg_replace('/[^a-z0-9]/', '_', $tenant);
        $dbPass = bin2hex(random_bytes(16));
        $dbRoot = bin2hex(random_bytes(16));

        return implode("\n", [
            "TENANT_NAME={$tenant}",
            "TENANT_DB_USER={$dbUser}",
            "TENANT_DB_PASS={$dbPass}",
            "TENANT_DB_ROOT={$dbRoot}",
            "TARGET_ENV=" . (getenv('TARGET_ENV') ?: 'dev'),
            "HOST_PROJECT_ROOT=" . ($scvRows['HOST_PROJECT_ROOT'] ?: ''),
            "TENANT_APP_IMAGE=" . ($scvRows['TENANT_APP_IMAGE'] ?: 'local-dockerlempapp:latest'),
            "TENANT_DB_IMAGE=" . ($scvRows['TENANT_DB_IMAGE'] ?: 'local-dockerlempdb:latest'),
            "TENANT_APP_VOLUME=" . ($scvRows['TENANT_APP_VOLUME'] ?: ''),
        ]);
    }

    private function generateNginxConf(string $tenant): string {
        global $systemConfigController;
        $externalPort = $systemConfigController->getSysConfig('EXTERNAL_PORT') ?: '443';
        $containerName = "{$tenant}-app-1";
        return <<<NGINX
location = /{$tenant} {
    return 301 \$scheme://\$http_host/{$tenant}/;
}

location /{$tenant}/ {
    resolver 127.0.0.11 valid=30s;
    
    set \$upstream_endpoint "https://{$tenant}-app-1:443";
    rewrite ^/{$tenant}/(.*)$ /\$1 break;
    
    proxy_pass \$upstream_endpoint;
    
    proxy_ssl_verify    off;
    
    proxy_set_header    Host \$http_host; 
    proxy_set_header    X-Forwarded-Host \$http_host;
    proxy_set_header    X-Forwarded-Proto \$scheme; 
    proxy_set_header    X-Forwarded-Port {$externalPort};
    
    proxy_set_header    X-Real-IP \$remote_addr;
    proxy_set_header    X-Forwarded-For \$proxy_add_x_forwarded_for;
}
NGINX;
    }
}
?>
