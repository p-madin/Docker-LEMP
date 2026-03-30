<?php

class FormValidation {
    /**
     * Processes security sanitization, executes schema-based validation, and handles flash routing upon failure.
     * Returns sanitized data on success.
     */
    public static function processAndValidate($schemaName, $inputData, $formSchemas, $sessionController, $failRedirectClosure) {
        // 1. Setup Security Sanitization

        // Default strategy for single-line inputs (text, email, etc.)
        $strategy = new \App\Security\HtmlEscapeDecorator(
            new \App\Security\SingleLineDecorator(
                new \App\Security\WhitespaceNormalization(
                    new \App\Security\StripTagsDecorator(
                        new \App\Security\CleanSanitizer()
                    )
                )
            )
        );

        $security = new \App\Security\SecurityValidation();
        $security->setStrategy($strategy);

        // Apply per-field strategies driven by each field's declared input type
        if (isset($formSchemas[$schemaName])) {
            foreach ($formSchemas[$schemaName] as $field) {
                $fieldStrategy = match($field['type']) {
                    // Preserves newlines; collapses horizontal whitespace per line
                    'textarea' => new \App\Security\HtmlEscapeDecorator(
                                      new \App\Security\TextareaWhitespaceDecorator(
                                          new \App\Security\MultiLineNormalizeDecorator(
                                              new \App\Security\StripTagsDecorator(
                                                  new \App\Security\CleanSanitizer()
                                              )
                                          )
                                      )
                                  ),
                    // Strips everything but digits; no HTML sanitization needed
                    'number'   => new \App\Security\IntegerSanitizerDecorator(
                                      new \App\Security\CleanSanitizer()
                                  ),
                    // Skipped fields — sanitized separately or not at all
                    'password', 'hidden' => null,
                    default    => null, // falls back to global $strategy
                };
                if ($fieldStrategy !== null) {
                    $security->setFieldStrategy($field['name'], $fieldStrategy);
                }
            }
        }

        // Clean the data (except potentially sensitive fields)
        $dataToClean = $inputData;
        unset($dataToClean['password'], $dataToClean['confirm_password']); 
        $cleanData = $security->process($dataToClean);

        // 2. Validation
        $validator = new \App\Security\Validator($inputData);
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
                echo json_encode(['redirect' => $redirectUrl]);
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
