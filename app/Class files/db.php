<?php

include_once(__DIR__ . "/Dialect/DatabaseDialect.php");
include_once(__DIR__ . "/Dialect/ANSIStandardDialect.php");
include_once(__DIR__ . "/Dialect/MySQLDialect.php");
include_once(__DIR__ . "/Dialect/PostgresDialect.php");

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
        $maxRetries = 15;
        $attempt = 0;
        while (true) {
            try {
                return new PDO($this->dsn, $this->username, $this->password, $this->options);
            } catch (\PDOException $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new \PDOException($e->getMessage() . " (Failed after {$maxRetries} attempts)", (int)$e->getCode());
                }
                error_log("Database connection failed: {$e->getMessage()}. Retrying in 2 seconds... (Attempt {$attempt}/{$maxRetries})");
                sleep(2);
            }
        }
    }

    public function getDialect(): DatabaseDialect {
        $vendor = getenv('DB_VENDOR') ?: 'mysql';
        return match(strtolower($vendor)) {
            'pgsql' => new PostgresDialect(),
            'mysql', 'mariadb' => new MySQLDialect(),
            default => new class extends ANSIStandardDialect {}
        };
    }

}


?>