<?php

class db_connect_controller{
    public $dsn;
    public $username;
    public $password;
    public $options;

    public function __construct($dsn, $username, $password, $options){
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    public function connect(){
        $returnable;
        try {
            $returnable = new PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
        return $returnable;

    }

}


?>