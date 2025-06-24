<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\LikeDAO;
use Database\DatabaseManager;
use Models\Like;

class LikeDAOImpl implements LikeDAO {
    
    public function create(Like $like): bool {
        
        if ($like->getLikeId() !== null) {
            throw new \Exception("Cannot create a like with an existing ID. likeId: {$like->getLikeId()}");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO likes (
                user_id,
                post_id
            ) 
            VALUES (
                ?, 
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ii",
            [
                $like->getUserId(),
                $like->getPostId(),
            ]
        );

        if (!$result) {
            return false;
        }

        $like->setLikeId($mysqli->insert_id);

        return true;
    }

    public function exists(int $userId, int $postId): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                like_id,
                user_id,
                post_id
            FROM 
                likes
            WHERE 
                user_id = ?
            AND
                post_id = ?
            LIMIT 1;
        QUERY;

        $result = $mysqli->prepareAndFetchAll(
            $query,
            "ii",
            [
                $userId,
                $postId,
            ]
        );

        return count($result) > 0;
    }

    public function delete(int $userId, int $postId): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            DELETE FROM 
                likes
            WHERE 
                user_id = ?
            AND
                post_id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ii",
            [
                $userId,
                $postId,
            ]
        );

        return $result;
    }

}