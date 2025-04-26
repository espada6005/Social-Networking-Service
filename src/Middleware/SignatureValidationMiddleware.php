<?php

namespace Middleware;

use Helpers\ValidationHelper;
use Response\FlashData;
use Response\HTTPRenderer;
use Response\Render\RedirectRenderer;
use Routing\Route;

class SignatureValidationMiddleware implements Middleware {

    public function handle(callable $next): HTTPRenderer {
        
        $currentPath = $_SERVER["REQUEST_URI"] ?? "";
        $parsedUrl = parse_url($currentPath);
        $pathWithoutQuery = $parsedUrl["path"] ?? "";

        // 現在のパスをRouteオブジェクトを作成
        $route = Route::create($pathWithoutQuery, function(){});

        // URLに有効な署名があるかチェック
        if ($route->isSignedURLValid($_SERVER["HTTP_HOST"] . $currentPath)) {
            if (isset($_GET["expiration"]) && ValidationHelper::integer($_GET["expiration"]) < time()) {
                FlashData::setFlashData('error', "The URL has expired.");
                return new RedirectRenderer("");
            }

            // 署名が有効であれば、ミドルウェアチェインを進める
            return $next();
        } else {
            // 署名が有効でない場合、ランダムな部分にリダイレクトする
            FlashData::setFlashData("error", sprintf("Invalid URL (%s).", $pathWithoutQuery));
            return new RedirectRenderer("");
        }

    }

}
