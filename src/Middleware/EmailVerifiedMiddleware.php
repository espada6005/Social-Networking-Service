<?php

namespace Middleware;

use Helpers\Authenticate;
use Response\HTTPRenderer;
use Response\Render\RedirectRenderer;

class EmailVerifiedMiddleware implements Middleware {

    public function handle(callable $next) : HTTPRenderer {
        error_log("Ruuning email verification check...");

        if (!Authenticate::isLoggedin()) {
            return new RedirectRenderer("");
        }

        $authenticatedUser = Authenticate::getAuthenticatedUser();
        if ($authenticatedUser->getEmailConfirmedAt() === null) {
            return new RedirectRenderer("verify/resend");
        }

        return $next();
    }

}
