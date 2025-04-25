<?php

namespace Types;

enum ValueType: string {
    case STRING = "string";
    case USERNAME = "username";
    case EMAIL = "email";
    case PASSWORD = "password";
}