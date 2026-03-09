<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $sessionController->destroySession();
        header("location:/?error=csrf");
        exit;
    }
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
            $stmt->execute(['v'=>$isVerified, 'id'=>$_POST['auPK']]);
        }
    } else {
        // Build the update query
        $params = [
            'username' => $_POST['username'],
            'name'     => $_POST['name'],
            'age'      => $_POST['age'],
            'city'     => $_POST['city'],
            'email'    => $_POST['email'],
            'id'       => $_POST['auPK']
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
}

header("Location: account_management.php");
exit;
?>
