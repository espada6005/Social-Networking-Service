<?php

use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Routing\Route;

return  [
    "" => Route::create("", function(): HTTPRenderer {
        return new HTMLRenderer("pages/top", []);
    })->setMiddleware(["guest"]),
];