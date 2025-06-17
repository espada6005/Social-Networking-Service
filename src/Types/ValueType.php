<?php

namespace Types;

enum ValueType: string {
    case NAME = "name";
    case USERNAME = "username";
    case EMAIL = "email";
    case PASSWORD = "password";
}