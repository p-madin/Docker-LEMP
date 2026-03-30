<?php
/**
 * preinclude.php
 * Records HTTP client actions for access logging.
 */

if (isset($db) && isset($sessionController)) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Capture GET or POST data
    $requestData = !empty($_POST) ? $_POST : $_GET;
    
    // Mask sensitive data and sanitize for logging
    $logData = [];
    $sensitiveKeys = ['password', 'csrf_token', 'confirm_password', 'old_password', 'new_password'];
    
    foreach ($requestData as $key => $value) {
        if (in_array(strtolower($key), $sensitiveKeys)) {
            $logData[$key] = '********';
        } else {
            // Basic sanitization for log display integrity (Stored XSS prevention)
            $logData[$key] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $headers = print_r($logData, true);

    try {
        $qb = new QueryBuilder($dialect);
        $qb->table('httpAction');
        $sql = $qb->insert([
            'haSessionFK' => $sessionController->sessPK ?? 0,
            'haUserFK'    => $sessionController->getPrimary('userID') ?: 0,
            'haIP'        => $ip,
            'haURL'       => substr($url, 0, 512),
            'haReferrer'  => $referrer ? substr($referrer, 0, 512) : null,
            'haMethod'    => substr($method, 0, 8),
            'haUserAgent' => substr($userAgent, 0, 512),
            'haHeaders'   => $headers
        ]);

        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
    } catch (PDOException $e) {
        // Silently fail or log to error log to prevent breaking the application flow
        error_log("Failed to log httpAction: " . $e->getMessage());
    }
}
?>
