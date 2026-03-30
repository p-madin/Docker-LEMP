<?php

require_once(__DIR__ . '/../TestBase.php');
require_once(__DIR__ . '/../../Class files/QueryBuilder.php');
require_once(__DIR__ . '/../../Class files/Dialect/ANSIStandardDialect.php');
require_once(__DIR__ . '/../../Class files/Dialect/MySQLDialect.php');
require_once(__DIR__ . '/../../Class files/Dialect/PostgresDialect.php');

class QueryBuilderTest extends TestSuiteBase {

    public function __construct() {
        parent::__construct("QueryBuilder Dialect Generative Tests");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running QueryBuilder Tests...\n";
        $success = true;

        $tests = [
            'testMySQLSelect',
            'testPostgresSelect',
            'testMySQLInsert',
            'testMySQLUpdate',
            'testMySQLDelete',
            'testMySQLJoin'
        ];

        foreach ($tests as $method) {
            $GLOBALS['returnable'] .= " - Test: $method\n";
            try {
                if (!$this->$method()) {
                    $success = false;
                }
            } catch (\Exception $e) {
                $GLOBALS['returnable'] .= "     [FAIL] Exception: " . $e->getMessage() . "\n";
                $success = false;
            }
        }

        return $success;
    }

    protected function testMySQLSelect() {
        $qb = new QueryBuilder(new MySQLDialect());
        $sql = $qb->table('users')
                  ->select(['id', 'username', 'email'])
                  ->where('status', '=', 'active')
                  ->where('age', '>', 18)
                  ->orderBy('created_at', 'DESC')
                  ->limit(10)
                  ->offset(20)
                  ->toSQL();

        $bindings = $qb->getBindings();
        // Offset (i_2) comes before Limit (i_3) because of toSQL order
        $expectedSql = "SELECT `id`, `username`, `email` FROM `users` WHERE `status` = :i_0 AND `age` > :i_1 ORDER BY `created_at` DESC LIMIT :i_2 OFFSET :i_3";
        return $this->assertEquals($expectedSql, $sql, "MySQL SELECT Query Generation") &&
               $this->assertEquals(['i_0' => 'active', 'i_1' => 18, 'i_2' => 10, 'i_3' => 20], $bindings, "MySQL SELECT Bindings");
    }

    protected function testPostgresSelect() {
        $qb = new QueryBuilder(new PostgresDialect());
        $sql = $qb->table('users')
                  ->select(['id', 'username', 'email'])
                  ->where('status', '=', 'active')
                  ->orderBy('created_at', 'DESC')
                  ->limit(5)
                  ->offset(10)
                  ->toSQL();

        $bindings = $qb->getBindings();
        $expectedSql = 'SELECT "id", "username", "email" FROM "users" WHERE "status" = :i_0 ORDER BY "created_at" DESC LIMIT :i_1 OFFSET :i_2';
        return $this->assertEquals($expectedSql, $sql, "Postgres SELECT Query Generation") &&
               $this->assertEquals(['i_0' => 'active', 'i_1' => 5, 'i_2' => 10], $bindings, "Postgres SELECT Bindings");
    }

    protected function testMySQLInsert() {
        $qb = new QueryBuilder(new MySQLDialect());
        $sql = $qb->table('users')
                  ->insert(['username' => 'testuser', 'email' => 'test@example.com']);

        $bindings = $qb->getBindings();
        $expectedSql = "INSERT INTO `users` (`username`, `email`) VALUES (:i_0, :i_1)";
        return $this->assertEquals($expectedSql, $sql, "MySQL INSERT Query Generation") &&
               $this->assertEquals(['i_0' => 'testuser', 'i_1' => 'test@example.com'], $bindings, "MySQL INSERT Bindings");
    }

    protected function testMySQLUpdate() {
        $qb = new QueryBuilder(new MySQLDialect());
        $sql = $qb->table('users')
                  ->where('id', '=', 1)
                  ->update(['status' => 'inactive', 'login_attempts' => 0]);

        $bindings = $qb->getBindings();
        // Update first generates WHERE param (i_0), then SET params (i_1, i_2)
        $expectedSql = "UPDATE `users` SET `status` = :i_1, `login_attempts` = :i_2 WHERE `id` = :i_0";
        return $this->assertEquals($expectedSql, $sql, "MySQL UPDATE Query Generation") &&
               $this->assertEquals(['i_0' => 1, 'i_1' => 'inactive', 'i_2' => 0], $bindings, "MySQL UPDATE Bindings");
    }

    protected function testMySQLDelete() {
        $qb = new QueryBuilder(new MySQLDialect());
        $sql = $qb->table('users')
                  ->where('status', '=', 'banned')
                  ->delete();

        $bindings = $qb->getBindings();
        $expectedSql = "DELETE FROM `users` WHERE `status` = :i_0";
        return $this->assertEquals($expectedSql, $sql, "MySQL DELETE Query Generation") &&
               $this->assertEquals(['i_0' => 'banned'], $bindings, "MySQL DELETE Bindings");
    }

    protected function testMySQLJoin() {
        $qb = new QueryBuilder(new MySQLDialect());
        $sql = $qb->table('users')
                  ->select(['users.username', 'profiles.bio'])
                  ->join('profiles', 'users.id', '=', 'profiles.user_id')
                  ->where('users.status', '=', 'active')
                  ->toSQL();

        $expectedSql = "SELECT `users`.`username`, `profiles`.`bio` FROM `users` INNER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id` WHERE `users`.`status` = :i_0";
        return $this->assertEquals($expectedSql, $sql, "MySQL JOIN Query Generation");
    }

    private function assertEquals($expected, $actual, $message) {
        if ($expected !== $actual) {
            $expectedStr = is_array($expected) ? json_encode($expected) : (string)$expected;
            $actualStr = is_array($actual) ? json_encode($actual) : (string)$actual;
            $GLOBALS['returnable'] .= "     [FAIL] $message\n";
            $GLOBALS['returnable'] .= "            Expected: $expectedStr\n";
            $GLOBALS['returnable'] .= "            Got:      $actualStr\n";
            
            if (is_string($expected) && is_string($actual)) {
                $GLOBALS['returnable'] .= "            HEX EXP: " . bin2hex($expected) . "\n";
                $GLOBALS['returnable'] .= "            HEX GOT: " . bin2hex($actual) . "\n";
            }
            return false;
        } else {
            $GLOBALS['returnable'] .= "     [OK] $message\n";
            return true;
        }
    }
}

$test_suite[] = new QueryBuilderTest();
