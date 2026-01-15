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
    $headers = print_r($requestData, true);

    try {
        $stmt = $db->prepare("
            INSERT INTO httpAction (
                haSessionFK, 
                haUserFK,
                haIP, 
                haURL, 
                haReferrer, 
                haMethod, 
                haUserAgent, 
                haHeaders
            ) VALUES (
                :sessionFK, 
                :userFK,
                :ip, 
                :url, 
                :referrer, 
                :method, 
                :userAgent, 
                :headers
            )
        ");

        $stmt->execute([
            ':sessionFK' => $sessionController->sessPK ?? 0,
            ':userFK'    => $sessionController->getPrimary('userID'),
            ':ip'        => $ip,
            ':url'       => substr($url, 0, 512),
            ':referrer'  => $referrer ? substr($referrer, 0, 512) : null,
            ':method'    => substr($method, 0, 8),
            ':userAgent' => substr($userAgent, 0, 512),
            ':headers'   => $headers
        ]);
    } catch (PDOException $e) {
        // Silently fail or log to error log to prevent breaking the application flow
        error_log("Failed to log httpAction: " . $e->getMessage());
    }
}
?>
