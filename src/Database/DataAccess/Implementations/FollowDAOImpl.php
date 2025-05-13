<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\FollowDAO;
use Database\DatabaseManager;
use Models\Follow;

class FollowDAOImpl implements FollowDAO {

    public function create(Follow $follow): bool {
        if ($follow->getFollowId() !== null) {
            throw new \InvalidArgumentException("すでにフォロー済みです");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO follows (
                follower_id,
                followee_id
            )
            VALUES (
                ?,
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "dd",
            [
                $follow->getFollowerId(),
                $follow->getFolloweeId(),
            ]
        );

        if (!$result) {
            return false;
        }

        $follow->setFollowId($mysqli->insert_id);

        return true;

    }

    public function getFollowers(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                users
            INNER JOIN
                follows 
            ON 
                users.user_id = follows.follower_id
            WHERE
                follows.followee_id = ?
            LIMIT 
                ? 
            OFFSET
                ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iii", [$user_id, $limit, $offset]) ?? null;
    
        return $result ?? null;
    }

    public function getFollowerCount(int $user_id): int {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT
                COUNT(*) AS count
            FROM
                follows
            WHERE
                followee_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "d", [$user_id]) ?? null;

        if ($result == null || !isset($result[0]["count"])) {
            return 0;
        }

        return $result[0]["count"];
    }

    public function isFollower(int $user_id, int $follower_id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT
                follow_id,
                follower_id,
                followee_id
            FROM
                follows 
            WHERE
                followee_id = ?
            AND
                follower_id = ?
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "dd", [$user_id, $follower_id]) ?? null;

        return $result !== null && count($result) > 0;
    }

    
    public function getFollowees(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM
                users
            INNER JOIN
                follows
            ON users.user_id = follows.followee_id 
            WHERE
                follows.follower_id = ? 
            LIMIT
                ?
            OFFSET
                ?";
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iii", [$user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getFolloweeCount(int $user_id): int {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                COUNT(*) AS count
            FROM
                follows
            WHERE
                followee_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "d", [$user_id]) ?? null;

        if ($result == null || !isset($result[0]["count"])) {
            return 0;
        }

        return $result[0]["count"];
    }

    public function isFollowee(int $user_id, int $followee_id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT
                follow_id,
                follower_id,
                followee_id
            FROM
                follows
            WHERE 
                followee_id = ? 
            AND 
                follower_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "dd", [$followee_id, $user_id]) ?? null;

        return $result !== null && count($result) > 0;
    }

    public function delete(int $user_id, int $followee_id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            DELETE FROM 
                follows 
            WHERE 
                follower_id = ? 
            AND 
                followee_id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute($query, "dd", [$user_id, $followee_id]);

        return $result;
    }

}