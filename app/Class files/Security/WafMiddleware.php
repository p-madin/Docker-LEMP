<?php
class WafMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db_controller, $db, $dialect;
        $ip = $request->server['REMOTE_ADDR'] ?? '';

        $rateLimiter = new RateLimiter($db, $dialect);

        if (empty($ip)) {
            return $next($request);
        }

        // 1. Is banned?
        if ($rateLimiter->checkBan($ip)) {
            http_response_code(403);
            die("403 Forbidden: You are temporarily blocked.");
        }

        // 2. Rate limit
        if (!$rateLimiter->incrementAndCheck($ip)) {
            http_response_code(429);
            die("429 Too Many Requests");
        }

        // 3. Payload inspection
        $this->inspectPayload($request->get, $ip, $request);
        $this->inspectPayload($request->post, $ip, $request);

        return $next($request);
    }

    protected function inspectPayload(array $payload, string $ip, Request $request) {
        global $db, $dialect;
        
        // Let's use a standard HtmlEscapeDecorator as baseline to check if input had weird chars
        $sanitizer = new \App\Security\HtmlEscapeDecorator(new \App\Security\CleanSanitizer());

        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/UNION SELECT/i'
        ];

        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $triggered = false;
                $rule = '';
                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $triggered = true;
                        $rule = "Matched pattern: $pattern";
                        break;
                    }
                }

                if ($ip != "127.0.0.1" && $triggered) {
                    // Log to WAF inside the existing httpAction
                    $qb = new QueryBuilder($dialect);
                    // Update the last recorded httpAction for this IP (which should be the current request from preinclude.php)
                    $sql = $qb->table('httpAction')
                              ->where('haIP', '=', $ip)
                              ->update([
                                  'haWafPayload' => substr($key . "=" . $value, 0, 65535),
                                  'haWafRuleTriggered' => substr($rule, 0, 255)
                              ]) . " ORDER BY haPK DESC LIMIT 1";

                    $stmt = $db->prepare($sql);
                    $qb->bindTo($stmt);
                    $stmt->execute();

                    $rateLimiter = new RateLimiter($db, $dialect);
                    $rateLimiter->banIp($ip, 'Malicious payload detected', 60);

                    http_response_code(403);
                    die("403 Forbidden: Malicious payload blocked.");
                }
            }
        }
    }
}
?>
