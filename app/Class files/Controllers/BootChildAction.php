<?php
class BootChildAction implements ControllerInterface {
    public static string $path = '/bootChild';
    public bool $isAction = true;

    public function execute(Request $request) {
        // global $sessionController;

        // $output = exec('cd /home/ubuntu/Workspace && docker-compose -f ./compose.super.remote.yaml --env-file ./.env.dev up --build');

        // var_dump($output);

        $tenant = 'customer-a'; // derive from request
        $tenantDir = "/home/ubuntu/Workspace/tenants/{$tenant}";

        // 1 — write the tenant compose file from template
        $compose = $this->generateComposeFile($tenant);
        mkdir($tenantDir, 0755, true);
        file_put_contents("{$tenantDir}/compose.yaml", $compose);

        // 2 — write the tenant env file
        $env = $this->generateEnvFile($tenant);
        file_put_contents("{$tenantDir}/.env", $env);
        shell_exec("docker network create superhost-network 2>/dev/null || true");
        // 3 — bring the stack up
        $output = shell_exec(
            "DOCKER_HOST=tcp://host.docker.internal:2375 docker-compose -f {$tenantDir}/compose.yaml --env-file {$tenantDir}/.env up -d 2>&1"
        );
        //wait for the compose to complete...
        sleep(3);

        // 4 — write nginx config and reload
        $nginxConf = $this->generateNginxConf($tenant);
        file_put_contents("/usr/local/nginx/conf.d/{$tenant}.conf", $nginxConf);
        $testOutput = shell_exec("sudo /usr/local/nginx/nginx -t 2>&1");

        if (strpos($testOutput, 'successful') !== false) {
            shell_exec("sudo /usr/local/nginx/nginx -s reload 2>&1");
        } else {
            // log $testOutput, don't reload
            error_log("Nginx config invalid for tenant {$tenant}: {$testOutput}");
        }
        echo "<pre>";
        var_dump($output);
        echo "</pre>";
    }
    private function generateComposeFile(string $tenant): string {
        $template = file_get_contents('/home/ubuntu/Workspace/compose.tenant.yaml');

        // Inject the project name so Docker namespaces containers
        // as tenant-a-app-1, tenant-a-db-1 etc.
        return "name: {$tenant}\n\n" . $template;
    }

    private function generateEnvFile(string $tenant): string {
        $dbUser = 'user_' . preg_replace('/[^a-z0-9]/', '_', $tenant);
        $dbPass = bin2hex(random_bytes(16));
        $dbRoot = bin2hex(random_bytes(16));

        // Persist credentials to tblTenants before returning
        //$this->storeTenantCredentials($tenant, $dbUser, $dbPass);

        return implode("\n", [
            "TENANT_NAME={$tenant}",
            "TENANT_DB_USER={$dbUser}",
            "TENANT_DB_PASS={$dbPass}",
            "TENANT_DB_ROOT={$dbRoot}",
            "TARGET_ENV=" . (getenv('TARGET_ENV') ?: 'dev'),
        ]);
    }
    private function generateNginxConf(string $tenant): string {
        $containerName = "{$tenant}-app-1";
        $devCert = '/var/www/html/nginx-selfsigned.crt';
        $devKey  = '/var/www/html/nginx-selfsigned.key';

        return <<<NGINX
location = /{$tenant} {
    return 301 \$scheme://\$http_host/{$tenant}/;
}

location /{$tenant}/ {
    proxy_pass          https://{$containerName}:443/;
    proxy_set_header    Host \$host;
    proxy_set_header    X-Real-IP \$remote_addr;
    proxy_set_header    X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header    X-Forwarded-Proto \$scheme;
    proxy_ssl_verify    off;
}
NGINX;
    }
}
?>
