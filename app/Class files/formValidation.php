<?php

class FormValidation {
    /**
     * Processes security sanitization, executes schema-based validation, and handles flash routing upon failure.
     * Returns sanitized data on success.
     */
    public static function processAndValidate($schemaName, $inputData, $formSchemas, $sessionController, $failRedirectClosure) {
        // 1. Setup Security Sanitization

        $security = new \App\Security\SecurityValidation();
        
        if (isset($formSchemas[$schemaName])) {
            $security->configureFromSchema($formSchemas[$schemaName]);
        } else {
            // Fallback for when no schema is provided
            $security->setStrategy(\App\Security\SanitizerFactory::createDefault());
        }

        // Clean the data (except potentially sensitive fields)
        $dataToClean = $inputData;
        unset($dataToClean['password'], $dataToClean['confirm_password']); 
        $cleanData = $security->process($dataToClean);

        // 2. Validation
        global $db;
        $validator = new \App\Security\Validator($inputData);
        if (isset($db)) {
            $validator->setDb($db);
        }

        if (isset($formSchemas[$schemaName])) {
            foreach ($formSchemas[$schemaName] as $field) {
                if (!empty($field['rules'])) {
                    $validator->rule($field['name'], $field['rules']);
                }
            }
        }

        if ($validator->fails()) {
            $errors = $sessionController->getPrimary('form_errors') ?? [];
            $errors[$schemaName] = $validator->errors();
            $sessionController->setPrimary('form_errors', $errors);
            
            $formData = $sessionController->getPrimary('form_data') ?? [];
            $formData[$schemaName] = $cleanData; // Use cleaned data for retention
            $sessionController->setPrimary('form_data', $formData);

            $redirectUrl = is_callable($failRedirectClosure) ? $failRedirectClosure($cleanData) : $failRedirectClosure;

            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['errors' => $validator->errors()]);
                exit;
            }
            header("Location: " . $redirectUrl);
            exit;
        }

        // Clear previous errors and data on success
        $errors = $sessionController->getPrimary('form_errors') ?? [];
        unset($errors[$schemaName]);
        $sessionController->setPrimary('form_errors', $errors);

        $formData = $sessionController->getPrimary('form_data') ?? [];
        unset($formData[$schemaName]);
        $sessionController->setPrimary('form_data', $formData);
        
        return $cleanData;
    }
}
?>
