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

        // Wait an extra moment to let the db and app fully initialize
        $GLOBALS['returnable'] .= "  -> Waiting 20 seconds for containers to initialize...\n";
        sleep(20); 

        // 2. Sync the tenant status
        $GLOBALS['returnable'] .= "  -> Syncing status\n";
        $syncResult = $manager->sync($tenantName);
        if (!$syncResult['is_active']) {
            $GLOBALS['returnable'] .= "  [FAIL] Tenant sync reported inactive. Docker: {$syncResult['docker_status']}, HTTP: {$syncResult['http_code']}\n  Output: " . substr($syncResult['curl_output'], 0, 500) . "\n";
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
