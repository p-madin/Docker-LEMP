<?php

class SessionController{
    public $db;
    public $sessPK;
    public function __construct($db){
        $this->db = $db;
    }
    public function seed(){
        global $scvRows;
        if(isset($_COOKIE["session"])) {
            //we need to check if this is in the database
            $query = $this->db->prepare("SELECT sessPK FROM tblSession WHERE sessChars = :i_sessChars");
            $query->bindParam("i_sessChars", $_COOKIE["session"]);
            $query->execute();
            $data = $query->fetchAll();
            if(count($data) == 0){
                //was set but it didnt exist... redirect to login
                setcookie("session", "", [
                    'expires' => time() - 3600, 
                    'path' => '/',
                    'domain' => $scvRows['myDomain'],
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Strict'
                 ]);
                header("Location: /index.php");
                exit;
            }else{
                $query = $this->db->prepare("UPDATE tblSession SET sessUpdated = NOW() WHERE sessChars = :i_sessChars");
                $query->bindParam("i_sessChars", $_COOKIE["session"]);
                $query->execute();
                $this->sessPK = $data[0]["sessPK"];
            }
        }else{
            //we need to set this
            $randString = "";
            $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            for($i = 0; $i < 64; $i++){
                $randString = $randString . chr(rand(65, 122));
            }
            $randString = substr(md5(microtime()),0,64);
            
            setcookie("session", $randString, [
                        'expires' => time() + (3600), 
                        'path' => '/',
                        'domain' => $scvRows['myDomain'],
                        'secure' => false,
                        'httponly' => true,
                        'samesite' => 'Strict'
                     ]
            );
            $query = $this->db->prepare("INSERT INTO tblSession (sessChars, sessTransactionActive) 
                                         VALUES (:i_sessChars, 0)");
            $query->bindParam("i_sessChars", $randString);
            $query->execute();
            $this->sessPK = $this->db->lastInsertId();
            
            //$scvRows['myDomain']
        }
    }

    private function isList(array $arr) {
        if ($arr === []) return true;
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    private function saveNode($key, $value, $disc, $sessPK){
        // 1. Insert Attribute Node
        $q = $this->db->prepare("INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
                                 VALUES (:sess, :disc, :key, 0)");
        $q->bindParam("sess", $sessPK);
        $q->bindParam("disc", $disc);
        $q->bindParam("key", $key);
        $q->execute();
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
                $qv = $this->db->prepare("INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) VALUES (:att, :val)");
                $qv->bindParam("att", $attPK);
                $qv->bindParam("val", $v);
                $qv->execute();
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
                 $qv = $this->db->prepare("INSERT INTO tblSessionAttValue (sattvAttFK, sattvValueFK) VALUES (:att, :child)");
                 $qv->bindParam("att", $attPK);
                 $qv->bindParam("child", $childPK);
                 $qv->execute();
                 if($firstValPK == 0) $firstValPK = $this->db->lastInsertId();
            }
        }

        // 3. Update Primary Value FK
        if($firstValPK > 0){
            $qu = $this->db->prepare("UPDATE tblSessionAtt SET sattPrimaryValueFK = :val WHERE sattPK = :pk");
            $qu->bindParam("val", $firstValPK);
            $qu->bindParam("pk", $attPK);
            $qu->execute();
        }
        
        return $attPK;
    }

    private function collectIds($attPK, &$attIds, &$valIds){
        // Add current attribute to list
        $attIds[] = $attPK;

        // Find all values for this attribute
        $q = $this->db->prepare("SELECT sattvPK, sattvValueFK FROM tblSessionAttValue WHERE sattvAttFK = :att");
        $q->bindParam("att", $attPK);
        $q->execute();
        
        while($row = $q->fetch(PDO::FETCH_ASSOC)){
            $valIds[] = $row['sattvPK'];
            // If value points to a child attribute, recurse
            if($row['sattvValueFK']){
                $this->collectIds($row['sattvValueFK'], $attIds, $valIds);
            }
        }
    }

    private function buildNode($attPK){
        $q = $this->db->prepare("SELECT sattDisc FROM tblSessionAtt WHERE sattPK = :att");
        $q->bindParam("att", $attPK);
        $q->execute();
        $disc = $q->fetchColumn();
        
        $q2 = $this->db->prepare("SELECT * FROM tblSessionAttValue WHERE sattvAttFK = :att ORDER BY sattvPK ASC");
        $q2->bindParam("att", $attPK);
        $q2->execute();
        $rows = $q2->fetchAll(PDO::FETCH_ASSOC);
        
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
                    $qk = $this->db->prepare("SELECT sattKey FROM tblSessionAtt WHERE sattPK = :cpk");
                    $qk->bindParam("cpk", $r['sattvValueFK']);
                    $qk->execute();
                    $key = $qk->fetchColumn();
                    $arr[$key] = $this->buildNode($r['sattvValueFK']);
                }
            }
            return $arr;
        }
    }

    public function detachPrimary($key){
        if(!$this->sessPK) return;
        
        $q = $this->db->prepare("SELECT sattPK FROM tblSessionAtt WHERE sattSessionFK = :sess AND sattKey = :key AND sattDisc = 'r'");
        $q->bindParam("sess", $this->sessPK);
        $q->bindParam("key", $key);
        $q->execute();
        $row = $q->fetch();
        
        if($row){
            $attIds = [];
            $valIds = [];
            $this->collectIds($row['sattPK'], $attIds, $valIds);
            
            if(!empty($valIds)){
                // Batch delete values
                // Use chunking to be safe with query size limits
                $chunks = array_chunk($valIds, 1000);
                foreach($chunks as $chunk){
                    $inQuery = implode(',', array_fill(0, count($chunk), '?'));
                    $stmt = $this->db->prepare("DELETE FROM tblSessionAttValue WHERE sattvPK IN ($inQuery)");
                    $stmt->execute($chunk);
                }
            }
            
            if(!empty($attIds)){
                // Batch delete attributes
                $chunks = array_chunk($attIds, 1000);
                foreach($chunks as $chunk){
                    $inQuery = implode(',', array_fill(0, count($chunk), '?'));
                    $stmt = $this->db->prepare("DELETE FROM tblSessionAtt WHERE sattPK IN ($inQuery)");
                    $stmt->execute($chunk);
                }
            }
        }
    }

    public function setPrimary($key, $array){
        $this->detachPrimary($key);
        
        $this->db->beginTransaction();
        try {
            $this->saveNode($key, $array, 'r', $this->sessPK);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getPrimary($key){        
        $q = $this->db->prepare("SELECT sattPK FROM tblSessionAtt WHERE sattSessionFK = :sess AND sattKey = :key AND sattDisc = 'r'");
        $q->bindParam("sess", $this->sessPK);
        $q->bindParam("key", $key);
        $q->execute();
        $row = $q->fetch();
        if($row){
            return $this->buildNode($row['sattPK']);
        }
        return null;
    }
}

?>