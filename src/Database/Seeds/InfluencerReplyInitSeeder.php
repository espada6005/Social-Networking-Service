<?php

namespace Database\Seeds;

use Faker\Factory;
use Database\AbstractSeeder;
use Database\MySQLWrapper;

require 'vendor/autoload.php';

class InfluencerReplyInitSeeder extends AbstractSeeder {
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
        $influencerIds = self::getAllProtInfluencerIds();

        for ($i = 0; $i < count($influencerIds); $i++) {
            $replyCount = rand(5, 10);
            $postIds = self::getProtInfluencerPostIds($influencerIds[$i], $replyCount);
            for ($j = 0; $j < count($postIds); $j++) {
                $posts[] = [
                    $influencerIds[$i],
                    $postIds[$j],
                    $faker->text(140),
                    "POSTED",
                ];
            }
        }

        return $posts;
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
            AND
                type = 'INFLUENCER';
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
                users 
            ON posts.user_id = users.user_id
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
}
