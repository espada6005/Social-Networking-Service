<?php

namespace Database\Seeds;

use Database\AbstractSeeder;
use Database\MySQLWrapper;

class UserFollowInitSeeder extends AbstractSeeder {
    // TODO: tableName文字列の割り当て
    protected ?string $tableName = "follows";

    // TODO: tableColumns配列の割り当て
    protected array $tableColumns = [
        [
            "data_type" => "int",
            "column_name" => "follower_id",
        ],
        [
            "data_type" => "int",
            "column_name" => "followee_id",
        ],
    ];

    public function createRowData(): array {
        // TODO: createRowData()メソッドの実装
        $follows = [];
        $userIds = self::getAllProtUserIds();

        for ($i = 0; $i < count($userIds); $i++) {
            // インフルエンサーのフォロー
            $influencerFollowCount = rand(10, 30);
            $followInfluencerIds = self::getProtInfluencerIds($influencerFollowCount);
            for ($j = 0; $j < count($followInfluencerIds); $j++) {
                $follows[] = [$userIds[$i], $followInfluencerIds[$j]];
            }

            // 一般ユーザーのフォロー
            $userFollowCount = rand(10, 30);
            $followUserIds = self::getProtUserIds($userIds[$i], $userFollowCount);
            for ($j = 0; $j < count($followUserIds); $j++) {
                $follows[] = [$userIds[$i], $followUserIds[$j]];
            }
        }

        return $follows;
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

    private function getProtInfluencerIds(int $limit): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                user_id
            FROM 
                users
            WHERE 
                email LIKE 'influencer%@example.com' 
            AND
                type = 'INFLUENCER'
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
            $influencerIds = array_map("intval", array_column($rows, "user_id"));
            return $influencerIds;
        }
        return [];
    }

    private function getProtUserIds(int $notUserId, int $limit): array {
        $mysqli = new MySQLWrapper();

        $query = <<<QUERY
            SELECT 
                user_id
            FROM 
                users
            WHERE 
                user_id != ? 
            AND
                email LIKE 'user%@example.com' 
            AND
                type != 'INFLUENCER'
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
            $userIds = array_map("intval", array_column($rows, "user_id"));
            return $userIds;
        }
        return [];
    }
}
