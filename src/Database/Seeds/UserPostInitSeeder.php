<?php

namespace Database\Seeds;

use Faker\Factory;
use Database\AbstractSeeder;
use Database\MySQLWrapper;

require 'vendor/autoload.php';

class UserPostInitSeeder extends AbstractSeeder {
    // TODO: tableName文字列の割り当て
    protected ?string $tableName = "posts";

    // TODO: tableColumns配列の割り当て
    protected array $tableColumns = [
        [
            "data_type" => "int",
            "column_name" => "user_id",
        ],
        [
            "data_type" => "string",
            "column_name" => "content",
        ],
        [
            "data_type" => "string",
            "column_name" => "status",
        ],
    ];

    public function createRowData(): array {
        // TODO: createRowData()メソッドの実装
        $faker = Factory::create();

        $posts = [];
        $userIds = self::getAllProtUserIds();

        for ($i = 0; $i < count($userIds); $i++) {
            $postCount = rand(10, 30);
            for ($j = 0; $j < $postCount; $j++) {
                $posts[] = [
                    $userIds[$i],
                    $faker->text(140),
                    "POSTED",
                ];
            }
        }

        return $posts;
    }

    private function getAllProtUserIds(): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                user_id
            FROM 
                users
            WHERE 
                email LIKE 'user%@example.com' 
            AND
                type != 'INFLUENCER';
        QUERY;

        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $userIds = array_map("intval", array_column($rows, "user_id"));
            return $userIds;
        }
        return [];
    }
}
