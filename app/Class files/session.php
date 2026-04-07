<?php

class SessionController{
    public $db;
    public $dialect;
    public $sessPK;
    public $isVerified = false;
    public function __construct($db, $dialect){
        $this->db = $db;
        $this->dialect = $dialect;
    }
    public function seed(){
        global $scvRows;
        // Ensure $scvRows is initialized if global lookup fails in some contexts
        
        if(isset($_COOKIE["session"])) {
            // Sanitize session cookie using the Security Strategy context
            $security = new \App\Security\SecurityValidation();
            $security->setStrategy(new \App\Security\AlphanumericDecorator(new \App\Security\CleanSanitizer()));
            $sanitizedSessChars = $security->process($_COOKIE["session"]);
            
            //we need to check if this is in the database
            $qb = new QueryBuilder($this->dialect);
            $qb->table('tblSession')->select(['sessPK'])->where('sessChars', '=', $sanitizedSessChars);
            $stmt = $this->db->prepare($qb->toSQL());
            $qb->bindTo($stmt);
            $stmt->execute();
            $data = $stmt->fetchAll();
            if(count($data) == 0){
                //was set but it didnt exist... redirect to login
                setcookie("session", "", [
                    'expires' => time() - 3600, 
                    'path' => '/',
                    'domain' => $scvRows['myDomain'] ?? '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Strict'
                    ]);
                header("Location: /index.php");
                exit;
            }else{
                $qb = new QueryBuilder($this->dialect);
                $qb->table('tblSession')->select(['sessPK', 'sessUser', 'sessTransactionActive'])->where('sessChars', '=', $sanitizedSessChars);
                $sql = $qb->update(['sessUpdated' => $qb->raw('NOW()')]);
                $stmt = $this->db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                $this->sessPK = $data[0]["sessPK"];
            }
        }else{
            //we need to set this
            $randString = "";
            while(!$this->sessPK){
                $randString = "";
                $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                $charLen = strlen($characters);
                for($i = 0; $i < 64; $i++){
                    $randString .= $characters[rand(0, $charLen - 1)];
                }
                $qb = new QueryBuilder($this->dialect);
                $qb->table('tblSession');
                $sql = $qb->insert(['sessChars' => $randString, 'sessTransactionActive' => 0]);
                $stmt = $this->db->prepare($sql);
                $qb->bindTo($stmt);
                try{
                    $stmt->execute();
                }catch(Exception $e){
                    //couldn't insert... try again
                    error_log("couldnt find a unique cookie string");
                    continue;
                }
                $this->sessPK = $this->db->lastInsertId();
            }
            
            setcookie("session", $randString, [
                      'expires' => time() + (3600), 
                      'path' => '/',
                      'domain' => $scvRows['myDomain'] ?? '',
                      'secure' => false,
                      'httponly' => true,
                      'samesite' => 'Strict'
                      ]
            );
            
            //$scvRows['myDomain']
        }
    }

    public function destroySession() {
        global $scvRows;
        setcookie("session", "", [
            'expires' => time() - 3600, 
            'path' => '/',
            'domain' => $scvRows['myDomain'],
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSession')->where('sessPK', '=', $this->sessPK);
        $sql = $qb->update(['sessDeleted' => $qb->raw('NOW()')]);
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $this->sessPK = null;
    }

    public function isLoggedIn(){
        $userID = $this->getPrimary('userID');
        if (is_null($userID)) return false;

        // Verify user status in database
        $qb = new QueryBuilder($this->dialect);
        $qb->table('appUsers')->select(['auPK', 'verified'])->where('auPK', '=', (int)$userID);
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user || ($user['verified'] ?? 0) == 0) {
            $this->isVerified = false;
            $this->destroySession();
            return false;
        }

        $this->isVerified = true;
        return true;
    }

    private function isList(array $arr) {
        if ($arr === []) return true;
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    private function saveNode($key, $value, $disc, $sessPK){
        // 1. Insert Attribute Node
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSessionAtt');
        $sql = $qb->insert([
            'sattSessionFK' => $sessPK,
            'sattDisc' => $disc,
            'sattKey' => $key,
            'sattPrimaryValueFK' => 0
        ]);
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $attPK = $this->db->lastInsertId();
        
        $mode = 'b';
        if($disc === 'l'){
             $mode = 'l';
        } elseif($disc === 'r'){
             // Root can be Leaf (scalar) or Branch (array)
             if(!is_array($value)){
                 $mode = 'l';
             } else {
                 $allScalars = true;
                 foreach($value as $v){ if(is_array($v)) $allScalars = false; }
                 
                 if(empty($value)){
                     $mode = 'b';
                 } elseif($this->isList($value) && $allScalars){
                     $mode = 'l';
                 } else {
                     $mode = 'b';
                 }
             }
        }

        $firstValPK = 0;

        // 2. Insert Values
        if($mode === 'l'){
            // Leaf: value is scalar or list of scalars
            $values = is_array($value) ? $value : [$value];
            foreach($values as $v){
                $qbv = new QueryBuilder($this->dialect);
                $qbv->table('tblSessionAttValue');
                $sqlv = $qbv->insert(['sattvAttFK' => $attPK, 'sattvValue' => $v]);
                $stmtv = $this->db->prepare($sqlv);
                $qbv->bindTo($stmtv);
                $stmtv->execute();
                if($firstValPK == 0) $firstValPK = $this->db->lastInsertId();
            }
        } else {
            // Branch: values are children
            foreach($value as $k => $v){
                 $childDisc = 'l';
                 if(is_array($v)){
                     $allScalars = true;
                     foreach($v as $sub){ if(is_array($sub)) $allScalars = false; }
                     
                     if(empty($v)){
                         $childDisc = 'b';
                     } elseif($this->isList($v) && $allScalars){
                         $childDisc = 'l';
                     } else {
                         $childDisc = 'b';
                     }
                 }
                 
                 // Recursive save
                 $childPK = $this->saveNode($k, $v, $childDisc, $sessPK);
                 
                 // Link to child
                 $qbv = new QueryBuilder($this->dialect);
                 $qbv->table('tblSessionAttValue');
                 $sqlv = $qbv->insert(['sattvAttFK' => $attPK, 'sattvValueFK' => $childPK]);
                 $stmtv = $this->db->prepare($sqlv);
                 $qbv->bindTo($stmtv);
                 $stmtv->execute();
                 if($firstValPK == 0) $firstValPK = $this->db->lastInsertId();
            }
        }

        // 3. Update Primary Value FK
        if($firstValPK > 0){
            $qbu = new QueryBuilder($this->dialect);
            $qbu->table('tblSessionAtt')->where('sattPK', '=', $attPK);
            $sqlu = $qbu->update(['sattPrimaryValueFK' => $firstValPK]);
            $stmtu = $this->db->prepare($sqlu);
            $qbu->bindTo($stmtu);
            $stmtu->execute();
        }
        
        return $attPK;
    }

    private function collectIds($attPK, &$attIds, &$valIds){
        // Add current attribute to list
        $attIds[] = $attPK;

        // Find all values for this attribute
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSessionAttValue')
            ->select(['sattvPK', 'sattvValueFK'])
            ->where('sattvAttFK', '=', $attPK);
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $valIds[] = $row['sattvPK'];
            // If value points to a child attribute, recurse
            if($row['sattvValueFK']){
                $this->collectIds($row['sattvValueFK'], $attIds, $valIds);
            }
        }
    }

    private function buildNode($attPK){
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSessionAtt')->select(['sattDisc'])->where('sattPK', '=', $attPK);
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        $disc = $stmt->fetchColumn();
        
        $qb2 = new QueryBuilder($this->dialect);
        $qb2->table('tblSessionAttValue')
            ->select(['sattvPK', 'sattvAttFK', 'sattvValueFK', 'sattvValue'])
            ->where('sattvAttFK', '=', $attPK)
            ->orderBy('sattvPK', 'ASC');
        $stmt2 = $this->db->prepare($qb2->toSQL());
        $qb2->bindTo($stmt2);
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        $treatAsLeaf = false;
        if($disc === 'l'){
             $treatAsLeaf = true;
        } elseif($disc === 'r' || $disc === 'b'){
             if(count($rows) > 0 && $rows[0]['sattvValueFK']){
                 $treatAsLeaf = false;
             } else {
                 if(count($rows) > 0) $treatAsLeaf = true;
                 else return []; 
             }
        }
        
        if($treatAsLeaf){
             if(count($rows) > 1){
                 $arr = [];
                 foreach($rows as $r) $arr[] = $r['sattvValue'];
                 return $arr;
             } elseif(count($rows) === 1){
                 return $rows[0]['sattvValue'];
             }
             return null;
        } else {
            $arr = [];
            foreach($rows as $r){
                if($r['sattvValueFK']){
                    $qbk = new QueryBuilder($this->dialect);
                    $qbk->table('tblSessionAtt')->select(['sattKey'])->where('sattPK', '=', $r['sattvValueFK']);
                    $stmtk = $this->db->prepare($qbk->toSQL());
                    $qbk->bindTo($stmtk);
                    $stmtk->execute();
                    $key = $stmtk->fetchColumn();
                    $arr[$key] = $this->buildNode($r['sattvValueFK']);
                }
            }
            return $arr;
        }
    }

    public function detachPrimary($key){
        if(!$this->sessPK) return;
        
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSessionAtt')
            ->select(['sattPK'])
            ->where('sattSessionFK', '=', $this->sessPK)
            ->where('sattKey', '=', $key)
            ->where('sattDisc', '=', 'r');
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        $row = $stmt->fetch();
        
        if($row){
            $attIds = [];
            $valIds = [];
            $this->collectIds($row['sattPK'], $attIds, $valIds);
            
            if(!empty($valIds)){
                // Batch delete values
                // Use chunking to be safe with query size limits
                $chunks = array_chunk($valIds, 1000);
                foreach($chunks as $chunk){
                    $qbv = new QueryBuilder($this->dialect);
                    $qbv->table('tblSessionAttValue')->whereIn('sattvPK', $chunk);
                    $stmtv = $this->db->prepare($qbv->delete());
                    $qbv->bindTo($stmtv);
                    $stmtv->execute();
                }
            }
            
            if(!empty($attIds)){
                // Batch delete attributes
                $chunks = array_chunk($attIds, 1000);
                foreach($chunks as $chunk){
                    $qba = new QueryBuilder($this->dialect);
                    $qba->table('tblSessionAtt')->whereIn('sattPK', $chunk);
                    $stmta = $this->db->prepare($qba->delete());
                    $qba->bindTo($stmta);
                    $stmta->execute();
                }
            }
        }
    }

    public function setPrimary($key, $array){
        $this->db->beginTransaction();
        try {
            $this->detachPrimary($key);
            $this->saveNode($key, $array, 'r', $this->sessPK);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getPrimary($key){        
        $qb = new QueryBuilder($this->dialect);
        $qb->table('tblSessionAtt')
            ->select(['sattPK'])
            ->where('sattSessionFK', '=', $this->sessPK)
            ->where('sattKey', '=', $key)
            ->where('sattDisc', '=', 'r');
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row){
            return $this->buildNode($row['sattPK']);
        }
        return null;
    }

    /**
     * Generates or retrieves a CSRF token for the current session.
     */
    public function getCSRFToken() {
        $token = $this->getPrimary('csrf_token');
        if (is_null($token)) {
            $token = bin2hex(random_bytes(32)); // 64 chars
            $this->setPrimary('csrf_token', $token);
        }
        return $token;
    }

    /**
     * Verifies the provided CSRF token against the session-stored token.
     */
    public function verifyCSRFToken($token) {
        $storedToken = $this->getPrimary('csrf_token');
        if (is_null($storedToken) || is_null($token)) {
            return false;
        }
        return hash_equals($storedToken, $token);
    }
}