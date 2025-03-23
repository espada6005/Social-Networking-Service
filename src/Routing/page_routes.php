<?php

use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Routing\Route;

return  [
    "" => Route::create("", function(): HTTPRenderer {
        return new HTMLRenderer("pages/home", []);
    })->setMiddleware(["guest"]),
    "login" => Route::create("/login", function(): HTTPRenderer {
        return new HTMLRenderer("pages/login", []);
    })->setMiddleware(["guest"]),
];