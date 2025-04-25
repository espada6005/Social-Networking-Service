<?php

use Helpers\Settings;

require_once "../vendor/autoload.php";

if (Settings::env("ENVIRONMENT") === "proc") {
    $DEBUG = false;
} else {
    $DEBUG = true;
}

if (preg_match('/\.(?:png|jpg|jpeg|gif|js|css|html)$/', $_SERVER["REQUEST_URI"])) {
    return false;
}

// ルートをロードする
$routes = include("../src/Routing/routes.php");

// リクエストURIからパスだけを解析して取得
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($path, '/');

// パスがルートに存在するかチェック
if (isset($routes[$path])) {
    $route = $routes[$path];
    try{
        if (!($route instanceof Routing\Route)) {
            throw new InvalidArgumentException("Invalid route type");
        }

        $middlewareRegister = include("../src/Middleware/middleware-register.php");
        $middlewares = array_merge($middlewareRegister['global'], array_map(fn($routeAlias) => $middlewareRegister['aliases'][$routeAlias], $route->getMiddleware()));
        $middlewareHandler = new \Middleware\MiddlewareHandler(array_map(fn($middlewareClass) => new $middlewareClass(), $middlewares));
    
        // チェーンの最後のcallableは、HTTPRendererを返す現在の$route callableとなる
        $renderer = $middlewareHandler->run($route->getCallback());

        // ヘッダーを設定します
        foreach ($renderer->getFields() as $name => $value) {
            // ヘッダーに対して単純なバリデーションを実行
            $sanitized_value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);

            if ($sanitized_value && $sanitized_value === $value) {
                header("{$name}: {$sanitized_value}");
            } else {
                // ヘッダーの設定が失敗した場合のログを取るか、または処理
                // エラー処理に応じて、例外を投げるか、またはデフォルトで続行する
                http_response_code(500);
                if($DEBUG) {
                    print("Failed setting header - original: '$value', sanitized: '$sanitized_value'");
                } else {
                    exit;
                }
            }
            print($renderer->getContent());
        }
    }
    catch (Exception $e) {
        http_response_code(500);
        print("Internal error, please contact the admin.<br>");
        if($DEBUG) {
            print($e->getMessage());
        }        
    }
} else {
    // ルートが一致しない場合は、404エラーを表示
    http_response_code(404);
    echo "404 Not Found: The requested route was not found on this server.";
}