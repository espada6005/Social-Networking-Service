<?php

namespace Middleware;

use Response\HTTPRenderer;

class SessionSetupMiddleware implements Middleware {

    public function handle(callable $next): HTTPRenderer {
        error_log("Setting up sessions...");
        session_start();

        return $next();
    }

}
