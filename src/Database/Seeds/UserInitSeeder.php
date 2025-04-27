<?php

namespace Database\Seeds;

use Faker\Factory; 
use Database\AbstractSeeder;
use Helpers\DateTimeHelper;

require 'vendor/autoload.php';

class UserInitSeeder extends AbstractSeeder {
    // TODO: tableName文字列の割り当て
    protected ?string $tableName = "users";

    // TODO: tableColumns配列の割り当て
    protected array $tableColumns = [
        [
            "data_type" => "string",
            "column_name" => "name",
        ],
        [
            "data_type" => "string",
            "column_name" => "username",
        ],
        [
            "data_type" => "string",
            "column_name" => "email",
        ],
        [
            "data_type" => "string",
            "column_name" => "password",
        ],
        [
            "data_type" => "string",
            "column_name" => "profile_text",
        ],
        [
            "data_type" => "string",
            "column_name" => "type",
        ],
        [
            "data_type" => "string",
            "column_name" => "email_confirmed_at",
        ],
    ];

    public function createRowData(): array {
        // TODO: createRowData()メソッドの実装
        $faker = Factory::create();

        $users = [];

        for ($i = 1; $i <= 100; $i++) {
            $type = $i <= 100 - 10 ? "USER" : "GUEST";
            $users[] = [
                $faker->userName(),
                $faker->word() . "u" . $i,
                "user" . $i . "@example.com",
                password_hash($faker->password(), PASSWORD_DEFAULT),
                $faker->text(100),
                $type,
                DateTimeHelper::formatDateTime(DateTimeHelper::getCurrentDateTime()),
            ];
        }

        return $users; 
    }
    
}