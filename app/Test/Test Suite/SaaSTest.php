<?php

class SaaSTest extends TestSuiteBase {

    public function test() {
        global $db, $dialect;

        $GLOBALS['returnable'] .= "Running SaaS Lifecycle Test...\n";

        // Setup a mock tenant in db
        $tenantName = 'test-tenant-' . time();
        $qb = new \QueryBuilder($dialect);
        
        $sql = $qb->table('absChildServices')->insert([
            'csCreatedByFK' => 1,
            'csAdminFK' => 1,
            'csName' => $tenantName,
            'csStatus' => 'u'
        ]);
        $qb->doExecute($db, $sql);

        $manager = new ChildServiceManager();
        
        // 1. Start the tenant
        $GLOBALS['returnable'] .= "  -> Starting tenant {$tenantName}\n";
        $result = $manager->start($tenantName);
        if ($result['status'] !== 'success') {
            $GLOBALS['returnable'] .= "  [FAIL] Failed to start tenant\n";
            $this->cleanup($tenantName);
            return false;
        }

        // Wait for the db and app to fully initialize (poll for up to 60 seconds)
        $GLOBALS['returnable'] .= "  -> Waiting for containers to initialize (polling up to 60s)...\n";
        
        $syncResult = null;
        for ($i = 0; $i < 12; $i++) {
            sleep(5);
            $syncResult = $manager->sync($tenantName);
            if ($syncResult['is_active']) {
                $GLOBALS['returnable'] .= "  -> Sync successful after " . (($i + 1) * 5) . " seconds\n";
                break;
            }
        }

        if (!$syncResult || !$syncResult['is_active']) {
            $dockerStatus = $syncResult['docker_status'] ?? 'unknown';
            $httpCode = $syncResult['http_code'] ?? 0;
            $curlOut = isset($syncResult['curl_output']) ? substr($syncResult['curl_output'], 0, 500) : '';
            $GLOBALS['returnable'] .= "  [FAIL] Tenant sync reported inactive. Docker: {$dockerStatus}, HTTP: {$httpCode}\n  Output: {$curlOut}\n";
            $this->cleanup($tenantName);
            return false;
        }

        // 3. Delete the tenant
        $GLOBALS['returnable'] .= "  -> Deleting tenant\n";
        $manager->delete($tenantName);

        $this->cleanup($tenantName);
        $GLOBALS['returnable'] .= "  [PASS] SaaS Lifecycle Test Complete\n";
        return true;
    }

    private function cleanup($tenantName) {
        global $db, $dialect;
        $qb = new \QueryBuilder($dialect);
        $sql = $qb->table('absChildServices')->where('csName', '=', $tenantName)->delete();
        $qb->doExecute($db, $sql);
        
        $tenantDir = "/home/ubuntu/Workspace/tenants/{$tenantName}";
        if (is_dir($tenantDir)) {
            shell_exec("rm -rf " . escapeshellarg($tenantDir));
        }
        $nginxConf = "/usr/local/nginx/conf.d/{$tenantName}.conf";
        if (file_exists($nginxConf)) {
            unlink($nginxConf);
            shell_exec("sudo /usr/local/nginx/nginx -s reload 2>&1");
        }
    }
}

global $test_suite;
$test_suite[] = new SaaSTest("SaaS Lifecycle Test");
?>
