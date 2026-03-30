<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

Hyperlink::handleAction($sessionController);

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $cleanData = FormValidation::processAndValidate('editUser', $_POST, $formSchemas, $sessionController, function($clean) {
        return "/edit_account.php?id=" . $clean['auPK'];
    });

    $myID = (int)$sessionController->getPrimary('userID');
    $qb = new QueryBuilder($dialect);
    $sql = $qb->table('appUsers')->select(['verified'])->where('auPK', '=', $myID)->toSQL();
    $stmt = $db->prepare($sql);
    $qb->bindTo($stmt);
    $stmt->execute();
    $me = $stmt->fetch();
    $iAmVerified = $me['verified'] ?? 0;

    if(isset($_POST['toggle_verify'])){
        // Legacy toggle support (if needed)
        if($iAmVerified){
            $isVerified = (isset($_POST['is_verified']) && $_POST['is_verified'] === '1') ? 1 : 0;
            $qb = new QueryBuilder($dialect);
            $sql = $qb->table('appUsers')->where('auPK', '=', $cleanData['auPK'])->update(['verified' => $isVerified]);
            $stmt = $db->prepare($sql);
            $qb->bindTo($stmt);
            $stmt->execute();
        }
    } else {
        // Build the update query
        $updateData = [
            'username' => $cleanData['username'],
            'name'     => $cleanData['name'],
            'age'      => (int)$cleanData['age'],
            'city'     => $cleanData['city'],
            'email'    => $cleanData['email']
        ];

        // Admin verification update
        if($iAmVerified){
            $updateData['verified'] = (isset($_POST['verified_status']) && $_POST['verified_status'] === '1') ? 1 : 0;
        }

        $qb = new QueryBuilder($dialect);
        $sql = $qb->table('appUsers')->where('auPK', '=', (int)$cleanData['auPK'])->update($updateData);
        
        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
    }

    $redirectUrl = "/account_management.php";
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
