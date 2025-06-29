<?php

namespace Database\Seeds;

use Faker\Factory;
use Database\AbstractSeeder;
use Database\MySQLWrapper;

require 'vendor/autoload.php';

class LikeInitSeeder extends AbstractSeeder {
    // TODO: tableName文字列の割り当て
    protected ?string $tableName = "likes";

    // TODO: tableColumns配列の割り当て
    protected array $tableColumns = [
        [
            "data_type" => "int",
            "column_name" => "user_id",
        ],
        [
            "data_type" => "int",
            "column_name" => "post_id",
        ],
    ];

    public function createRowData(): array {
        // TODO: createRowData()メソッドの実装
        $faker = Factory::create();

        $likes = [];

        $influencerIds = self::getAllProtInfluencerIds();
        for ($i = 0; $i < count($influencerIds); $i++) {
            // インフルエンサー > インフルエンサーのポストいいね
            $postIds = self::getProtInfluencerPostIds($influencerIds[$i], 20);
            for ($j = 0; $j < count($postIds); $j++) {
                $likes[] = [$influencerIds[$i], $postIds[$j]];
            }

            // インフルエンサー > 一般ユーザーのポストいいね
            $postIds = self::getProtUserPostIds($influencerIds[$i], 5);
            for ($j = 0; $j < count($postIds); $j++) {
                $likes[] = [$influencerIds[$i], $postIds[$j]];
            }
        }

        $userIds = self::getAllProtUserIds();
        for ($i = 0; $i < count($userIds); $i++) {
            // 一般ユーザー > インフルエンサーのポストいいね
            $postIds = self::getProtInfluencerPostIds($userIds[$i], 20);
            for ($j = 0; $j < count($postIds); $j++) {
                $likes[] = [$userIds[$i], $postIds[$j]];
            }

            // 一般ユーザー > 一般ユーザーのポストいいね
            $postIds = self::getProtUserPostIds($userIds[$i], 5);
            for ($j = 0; $j < count($postIds); $j++) {
                $likes[] = [$userIds[$i], $postIds[$j]];
            }
        }

        return $likes;
    }

    private function getAllProtInfluencerIds(): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                user_id
            FROM 
                users
            WHERE 
                email LIKE 'influencer%@example.com' 
            AND type = 'INFLUENCER';
        QUERY;

        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $influencerIds = array_map("intval", array_column($rows, "user_id"));
            return $influencerIds;
        }
        return [];
    }

    private function getProtInfluencerPostIds(int $notUserId, int $limit): array {
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
            AND 
                users.email LIKE 'influencer%@example.com' 
            AND
                users.type = 'INFLUENCER'
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
