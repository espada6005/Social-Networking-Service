<?php

namespace Helpers;

class CrossSiteForgeryProtection {

    public static function getToken(): string {
        return $_SESSION["csrf_token"];
    }
    
}
