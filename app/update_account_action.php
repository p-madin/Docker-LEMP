<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $sessionController->destroySession();
        header("location:/?error=csrf");
        exit;
    }

    $cleanData = FormValidation::processAndValidate('editUser', $_POST, $formSchemas, $sessionController, function($clean) {
        return "/account_management.php?edit=" . ($clean['auPK'] ?? '');
    });

    $myID = (int)$sessionController->getPrimary('userID');
    $stmt = $db->prepare("SELECT verified FROM appUsers WHERE auPK = :id");
    $stmt->execute(['id'=>$myID]);
    $me = $stmt->fetch();
    $iAmVerified = $me['verified'] ?? 0;

    if(isset($_POST['toggle_verify'])){
        // Legacy toggle support (if needed)
        if($iAmVerified){
            $isVerified = isset($_POST['is_verified']) ? 1 : 0;
            $stmt = $db->prepare("UPDATE appUsers SET verified = :v WHERE auPK = :id");
            $stmt->execute(['v'=>$isVerified, 'id'=>$cleanData['auPK']]);
        }
    } else {
        // Build the update query
        $params = [
            'username' => $cleanData['username'],
            'name'     => $cleanData['name'],
            'age'      => $cleanData['age'],
            'city'     => $cleanData['city'],
            'email'    => $cleanData['email'],
            'id'       => $cleanData['auPK']
        ];
        
        $sql = "UPDATE appUsers SET 
                    username = :username, 
                    name = :name, 
                    age = :age, 
                    city = :city, 
                    email = :email";

        // Admin verification update
        if($iAmVerified){
            $sql .= ", verified = :verified";
            $params['verified'] = isset($_POST['verified_status']) ? 1 : 0;
        }

        $sql .= " WHERE auPK = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    $redirectUrl = "/account_management.php?edit=" . $cleanData['auPK'];
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        echo json_encode(['redirect' => $redirectUrl]);
        exit;
    }

    header("Location: " . $redirectUrl);
    exit;
}

header("Location: /account_management.php");
exit;
?>
