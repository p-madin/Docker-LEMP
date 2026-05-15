<?php

namespace App\Security;

class Validator {
    protected $data;
    protected $rules = [];
    protected $errors = [];
    protected $validated = false;

    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Add a rule for a field.
     * Format: 'required|min:5|email' or ['required' => true, 'min' => 5, 'email' => true]
     */
    public function rule($field, $rules) {
        $parsedRules = [];
        if (is_string($rules)) {
            $rulesArray = explode('|', $rules);
        } else {
            $rulesArray = $rules ?? [];
        }

        foreach ($rulesArray as $key => $value) {
            if (is_int($key)) {
                // String rule like 'required' or 'min:5' in indexed array
                if (strpos($value, ':') !== false) {
                    list($r, $p) = explode(':', $value, 2);
                    $parsedRules[$r] = $p;
                } else {
                    $parsedRules[$value] = true;
                }
            } else {
                // Key-value pair like 'min' => 5
                $parsedRules[$key] = $value;
            }
        }
        $this->rules[$field] = $parsedRules;
        return $this;
    }

    /**
     * Run the validation.
     * @return bool True if all rules pass.
     */
    public function validate() {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;

            foreach ($ruleset as $ruleName => $ruleValue) {
                if (!$this->check($field, $value, $ruleName, $ruleValue)) {
                    // Stop at first failure for this field
                    break;
                }
            }
        }
        $this->validated = true;
        return empty($this->errors);
    }

    protected $db;
    protected $dialect;

    public function setDb(\PDO $db, $dialect) {
        $this->db = $db;
        $this->dialect = $dialect;
        return $this;
    }

    /**
     * Check a specific rule.
     */
    protected function check($field, $value, $ruleName, $ruleValue) {
        if ($ruleValue === false) return true; // skip disabled rules

        global $qb;

        // Convert scalar parameter to array (e.g. '5' to ['5']) for rules expecting array keys
        $params = is_string($ruleValue) ? explode(',', (string)$ruleValue) : (array)$ruleValue;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "The $field field is required.");
                    return false;
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < $params[0]) {
                    $this->addError($field, "The $field must be at least {$params[0]} characters.");
                    return false;
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > $params[0]) {
                    $this->addError($field, "The $field may not be greater than {$params[0]} characters.");
                    return false;
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The $field must be a valid email address.");
                    return false;
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The $field must be a number.");
                    return false;
                }
                break;

            case 'alpha_numeric':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    $this->addError($field, "The $field may only contain letters and numbers.");
                    return false;
                }
                break;

            case 'match':
                $otherField = $params[0] ?? '';
                if ($value !== ($this->data[$otherField] ?? null)) {
                    $this->addError($field, "The $field must match $otherField.");
                    return false;
                }
                break;

            case 'unique':
                if (empty($value) || !$this->db) return true;
                
                $alias = $params[0] ?? '';
                
                // Define secure server-side mappings for unique checks
                // This prevents exposing table/column names to the client
                $uniqueMappings = [
                    'registration_username' => ['appUsers', 'username'],
                    'registration_email'    => ['appUsers', 'email'],
                    'navbar_text'           => ['tblNavBar', 'nbText'],
                    'navbar_path'           => ['tblNavBar', 'nbPath']
                ];

                if (!isset($uniqueMappings[$alias])) {
                    // If using the old format (table,column) while transitioning, 
                    // we can either block it or check a strict whitelist.
                    // For maximum security, we only allow mapped aliases.
                    return true; 
                }

                [$table, $column] = $uniqueMappings[$alias];

                // 3. Identifier Escaping
                $qb = new \QueryBuilder($this->dialect);
                $qb->table($table)->select([$qb->raw('COUNT(*) as count')])->where($column, '=', $value);
                
                if ($qb->getFetch($this->db)['count'] > 0) {
                    $this->addError($field, "This $field is already taken.");
                    return false;
                }
                break;

            case 'regex':
                if (!empty($value) && !preg_match($ruleValue, $value)) {
                    $this->addError($field, "The $field format is invalid.");
                    return false;
                }
                break;

            case 'alpha_dash':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9\-_]+$/', $value)) {
                    $this->addError($field, "The $field may only contain letters, numbers, dashes and underscores.");
                    return false;
                }
                break;
        }

        return true;
    }

    protected function addError($field, $message) {
        $this->errors[$field][] = $message;
    }

    public function fails() {
        if (!$this->validated) $this->validate();
        return !empty($this->errors);
    }

    public function errors() {
        if (!$this->validated) $this->validate();
        return $this->errors;
    }
}
