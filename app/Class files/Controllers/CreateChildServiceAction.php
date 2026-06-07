<?php
class CreateChildServiceAction implements ControllerInterface {
    public static string $path = '/createChildService';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect, $sessionController;

        $rawName = $request->post['csName'] ?? '';
        $csAdminFK = $request->post['csAdminFK'] ?? '';

        // Sanitize: strip everything that isn't alphanumeric, dash, or underscore
        $sanitizer = new \App\Security\AlphaDashDecorator(
            new \App\Security\StripTagsDecorator(
                new \App\Security\CleanSanitizer()
            )
        );
        $csName = $sanitizer->sanitize($rawName);

        if (!$csName) {
            $this->redirectError('Service Name is required');
            return;
        }

        // Server-side validation
        $validator = new \App\Security\Validator(['csName' => $csName, 'csAdminFK' => $csAdminFK]);
        $validator->rule('csName', 'required|alpha_dash');
        $validator->rule('csAdminFK', 'required|numeric');
        if ($validator->fails()) {
            $errors = array_values($validator->errors())[0] ?? ['Invalid form data.'];
            $this->redirectError($errors[0]);
            return;
        }

        try {
            $userId = 1; // Fallback
            $sessionUser = $sessionController->getSystemUserId();
            if ($sessionUser) {
                $userId = $sessionUser;
            }

            $qb = new QueryBuilder($dialect);
            $sql = $qb->table('absChildServices')->insert([
                'csCreatedByFK' => $userId,
                'csAdminFK' => (int)$csAdminFK,
                'csName' => $csName,
                'csStatus' => 'u'
            ]);
            $qb->doExecute($db, $sql);
            
            header("Location: /child_management");
            exit;
        } catch (Exception $e) {
            error_log("CreateChildServiceAction error: " . $e->getMessage());
            $this->redirectError('An error occurred while creating child service');
        }
    }

    private function redirectError(string $message) {
        header("Location: /child_management?error=" . urlencode($message));
        exit;
    }
}
?>
