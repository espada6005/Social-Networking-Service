<?php

namespace Middleware;

use Helpers\Authenticate;
use Response\HTTPRenderer;

class GuestMiddleware implements Middleware {

    public function handle(callable $next): HTTPRenderer {
        error_log("Running authentication check...");

        // ユーザーがログインしている場合は、メッセージなしでランダムパーツのページにリダイレクトする
        if (Authenticate::isLoggedin()) {
            
        }

        return $next();
    }
    
}
