<?php

class SessionMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db, $dialect, $sessionController;

        // 1. Session Initialization
        $sessionController = new SessionController($db, $dialect);
        $sessionController->seed();

        // 2. Event Synchronisation
        // If a previous request (or an action in this request) has left a pending event,
        // we wait for the background worker to complete it before proceeding.
        // This ensures the next view reflects the processed state.
        $pendingEventId = $sessionController->getPrimary('pending_event_id');
        if ($pendingEventId) {
            $eventStore = new EventStore($db, $dialect);
            $eventStore->waitUntilProcessed((int)$pendingEventId);
            $sessionController->detachPrimary('pending_event_id');
        }

        // 3. PRG Redirect Handler
        // Hyperlink depends on SessionController
        Hyperlink::handleRedirect($sessionController);

        return $next($request);
    }
}
