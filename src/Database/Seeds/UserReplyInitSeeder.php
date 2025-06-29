<?php

namespace Database\Seeds;

use Faker\Factory;
use Database\AbstractSeeder;
use Database\MySQLWrapper;

require 'vendor/autoload.php';

class UserReplyInitSeeder extends AbstractSeeder {
    // TODO: tableName文字列の割り当て
    protected ?string $tableName = "posts";

    // TODO: tableColumns配列の割り当て
    protected array $tableColumns = [
        [
            "data_type" => "int",
            "column_name" => "user_id",
        ],
        [
            "data_type" => "int",
            "column_name" => "reply_to_id",
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
            // インフルエンサーのポストへのリプライ
            $replyCount = rand(5, 10);
            $postIds = self::getProtInfluencerPostIds($replyCount);
            for ($j = 0; $j < count($postIds); $j++) {
                $posts[] = [
                    $userIds[$i],
                    $postIds[$j],
                    $faker->text(140),
                    "POSTED",
                ];
            }

            // 一般ユーザーのポストへのリプライ
            $replyCount = rand(5, 10);
            $postIds = self::getProtUserPostIds($userIds[$i], $replyCount);
            for ($j = 0; $j < count($postIds); $j++) {
                $posts[] = [
                    $userIds[$i],
                    $postIds[$j],
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

    private function getProtInfluencerPostIds(int $limit): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                posts.post_id AS post_id
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            WHERE 
                users.email LIKE 'influencer%@example.com' 
            AND
                users.type = 'INFLUENCER'
            ORDER BY 
                RAND()
            LIMIT 
                ?;
        QUERY;

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $limit);

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $postIds = array_map("intval", array_column($rows, "post_id"));
            return $postIds;
        }
        return [];
    }

    private function getProtUserPostIds(int $notUserId, int $limit): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                posts.post_id AS post_id
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            WHERE 
                users.user_id != ? 
                AND users.email LIKE 'user%@example.com' 
                AND users.type != 'INFLUENCER'
            ORDER BY 
                RAND()
            LIMIT 
                ?;
        QUERY;

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $notUserId, $limit);

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $postIds = array_map("intval", array_column($rows, "post_id"));
            return $postIds;
        }
        return [];
    }
}
