<?php

class ValidationTest extends TestSuiteBase {
    public function __construct() {
        parent::__construct("Input Validation Test Suite");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Input Validation Test Suite...\n";
        $pass = true;

        // Autoloader for Validator (relative to this file)
        spl_autoload_register(function ($class) {
            if ($class === 'App\\Security\\Validator') {
                require_once __DIR__ . '/../../Class files/Security/Validator.php';
            }
        });

        // Test Helper
        $runTest = function($name, $callback) use (&$pass) {
            try {
                $callback();
                $GLOBALS['returnable'] .= "[PASS] $name\n";
            } catch (Exception $e) {
                $GLOBALS['returnable'] .= "[FAIL] $name: " . $e->getMessage() . "\n";
                $pass = false;
            }
        };

        // --- Test Cases ---

        $runTest("Required Rule", function() {
            $v = new \App\Security\Validator(['name' => '']);
            $v->rule('name', 'required');
            if ($v->validate()) throw new Exception("Should fail for empty string");
            
            $v = new \App\Security\Validator(['age' => '0']);
            $v->rule('age', 'required');
            if (!$v->validate()) throw new Exception("Should pass for '0'");
        });

        $runTest("Email Rule", function() {
            $v = new \App\Security\Validator(['email' => 'invalid-email']);
            $v->rule('email', 'email');
            if ($v->validate()) throw new Exception("Should fail for invalid email");

            $v = new \App\Security\Validator(['email' => 'test@example.com']);
            $v->rule('email', 'email');
            if (!$v->validate()) throw new Exception("Should pass for valid email");
        });

        $runTest("Length Rules (min/max)", function() {
            $v = new \App\Security\Validator(['pass' => '123']);
            $v->rule('pass', 'min:5');
            if ($v->validate()) throw new Exception("Should fail for shorter than 5");

            $v = new \App\Security\Validator(['pass' => '123456']);
            $v->rule('pass', 'max:5');
            if ($v->validate()) throw new Exception("Should fail for longer than 5");
        });

        $runTest("Numeric Rule", function() {
            $v = new \App\Security\Validator(['age' => 'abc']);
            $v->rule('age', 'numeric');
            if ($v->validate()) throw new Exception("Should fail for non-numeric");
        });

        $runTest("AlphaNumeric Rule", function() {
            $v = new \App\Security\Validator(['user' => 'user_123']);
            $v->rule('user', 'alpha_numeric');
            if ($v->validate()) throw new Exception("Should fail for underscore");
        });

        $runTest("Match Rule", function() {
            $v = new \App\Security\Validator(['p1' => 'secret', 'p2' => 'wrong']);
            $v->rule('p2', 'match:p1');
            if ($v->validate()) throw new Exception("Should fail when not matching");

            $v = new \App\Security\Validator(['p1' => 'secret', 'p2' => 'secret']);
            $v->rule('p2', 'match:p1');
            if (!$v->validate()) throw new Exception("Should pass when matching");
        });

        return $pass;
    }
}

$test_suite[] = new ValidationTest();
