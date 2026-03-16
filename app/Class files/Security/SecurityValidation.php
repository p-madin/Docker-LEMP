<?php

namespace App\Security;

include_once(__DIR__ . "/SanitizationStrategy.php");
include_once(__DIR__ . "/SanitizerDecorator.php");
include_once(__DIR__ . "/XssSanitizer.php");

//decorations
include_once(__DIR__ . "/AlphanumericDecorator.php");
include_once(__DIR__ . "/AttributeWhitelistDecorator.php");
include_once(__DIR__ . "/CleanSanitizer.php");
include_once(__DIR__ . "/HtmlEscapeDecorator.php");
include_once(__DIR__ . "/LengthLimitDecorator.php");
include_once(__DIR__ . "/MultiLineNormalizeDecorator.php");
include_once(__DIR__ . "/SingleLineDecorator.php");
include_once(__DIR__ . "/StripTagsDecorator.php");
include_once(__DIR__ . "/UrlSanitizerDecorator.php");
include_once(__DIR__ . "/RootRelativePathDecorator.php");
include_once(__DIR__ . "/IntegerSanitizerDecorator.php");
include_once(__DIR__ . "/WhitespaceNormalization.php");
include_once(__DIR__ . "/Validator.php");


/**
 * Class SecurityValidation
 * Manages the sanitization strategy.
 */
class SecurityValidation {
    protected $strategy;
    protected $fieldStrategies = [];

    public function setStrategy(SanitizationStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function setFieldStrategy(string $field, SanitizationStrategy $strategy) {
        $this->fieldStrategies[$field] = $strategy;
    }

    public function process($data, $fieldName = null) {
        if (!$this->strategy) {
            throw new Exception("Sanitization strategy not set.");
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->process($value, $key);
            }
            return $result;
        }

        // Use field-specific strategy if it exists
        $currentStrategy = ($fieldName && isset($this->fieldStrategies[$fieldName])) 
            ? $this->fieldStrategies[$fieldName] 
            : $this->strategy;

        return $currentStrategy->sanitize($data);
    }
}
