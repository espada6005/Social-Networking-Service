<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\PostDAO;
use Database\DatabaseManager;
use Models\Post;

class PostDAOImpl implements PostDAO {
    
    public function create(Post $post): bool {
        if ($post->getPostId() !== null) {
            throw new \Exception("Postすることはできません");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO posts (
                user_id,
                reply_to_id,
                content,
                status,
                image_hash,
                scheduled_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ddssss",
            [
                $post->getUserId(),
                $post->getReplyToId(),
                $post->getContent(),
                $post->getStatus(),
                $post->getImageHash(),
                $post->getScheduledAt(),
            ]
        );

        if (!$result) {
            return false;
        }

        $post->setPostId($mysqli->insert_id);

        return true;
    }

    public function postScheduledPosts(): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            UPDATE 
                posts
            SET 
                status = 'POSTED',
                scheduled_at = NULL
            WHERE 
                status = 'SCHEDULED'
            AND scheduled_at <= NOW();
        QUERY;

        $result = $mysqli->prepareAndExecute($query, "", []);

        return $result;
    }

    public function getPost(int $post_id, int $authenticated_user_id): ?array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id = ?
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                WHERE likes.post_id = ?
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.post_id = ? AND likes.user_id = ?
                GROUP BY likes.post_id
            )
            SELECT 
                posts.post_id,
                posts.reply_to_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE 
                posts.post_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiiii", [$post_id, $post_id, $post_id, $authenticated_user_id, $post_id]) ?? null;

        return $result ? $result[0] : null;
    }

    public function getReplies(int $post_id, int $authenticated_user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
                GROUP BY likes.post_id
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE
                posts.reply_to_id = ?
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count
            ORDER BY 
                posts.post_id DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiii", [$authenticated_user_id, $post_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getTrendTimelinePosts(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id IS NOT NULL
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
                GROUP BY likes.post_id
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE 
                posts.status = 'POSTED'
                AND posts.reply_to_id IS NULL
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count
            ORDER BY 
                like_count_table.like_count DESC,
                posts.updated_at DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iii", [$user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getFollowTimelinePosts(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id IS NOT NULL
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
                GROUP BY likes.post_id
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                follows ON users.user_id = follows.followee_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE 
                posts.status = 'POSTED'
                AND posts.reply_to_id IS NULL
                AND (follows.follower_id = ? OR posts.user_id = ?)
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count
            ORDER BY 
                posts.post_id DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiiii", [$user_id, $user_id, $user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getUserPosts(int $user_id, int $authenticated_user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id IS NOT NULL
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE 
                posts.status = 'POSTED'
                AND posts.user_id = ?
                AND posts.reply_to_id IS NULL
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count
            ORDER BY 
                posts.post_id DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiii", [$authenticated_user_id, $user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getUserReplies(int $user_id, int $authenticated_user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id IS NOT NULL
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            WHERE 
                posts.status = 'POSTED'
                AND posts.user_id = ?
                AND posts.reply_to_id IS NOT NULL
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count
            ORDER BY 
                posts.post_id DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiii", [$authenticated_user_id, $user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getScheduledPosts(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                post_id, content, image_hash, scheduled_at
            FROM 
                posts
            WHERE 
                status = 'SCHEDULED'
            AND user_id = ? 
            LIMIT ? 
            OFFSET ?;
        QUERY;
            
        $result = $mysqli->prepareAndFetchAll($query, "iii", [$user_id, $limit, $offset]);

        return $result ?? [];
    }

    public function getUserLikes(int $user_id, int $authenticated_user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            WITH reply_count_table AS (
                SELECT posts.reply_to_id, COUNT(*) AS reply_count
                FROM posts
                WHERE posts.reply_to_id IS NOT NULL
                GROUP BY posts.reply_to_id
            ),
            like_count_table AS (
                SELECT likes.post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY likes.post_id
            ),
            liked_table AS (
                SELECT likes.post_id
                FROM likes
                WHERE likes.user_id = ?
            ),
            liked_with_timestamp_table AS (
                SELECT likes.post_id, likes.created_at
                FROM likes
                WHERE likes.user_id = ?
            )
            SELECT 
                posts.post_id,
                posts.content,
                posts.image_hash,
                posts.updated_at,
                IFNULL(reply_count_table.reply_count, 0) AS reply_count,
                IFNULL(like_count_table.like_count, 0) AS like_count,
                CASE 
                    WHEN liked_table.post_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS liked,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                posts
            INNER JOIN 
                users ON posts.user_id = users.user_id
            LEFT JOIN 
                reply_count_table ON posts.post_id = reply_count_table.reply_to_id
            LEFT JOIN 
                like_count_table ON posts.post_id = like_count_table.post_id
            LEFT JOIN 
                liked_table ON posts.post_id = liked_table.post_id
            INNER JOIN 
                liked_with_timestamp_table ON posts.post_id = liked_with_timestamp_table.post_id
            WHERE 
                posts.status = 'POSTED'
            GROUP BY 
                posts.post_id,
                reply_count_table.reply_count,
                like_count_table.like_count,
                liked_with_timestamp_table.created_at
            ORDER BY 
                liked_with_timestamp_table.created_at DESC
            LIMIT ? OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiii", [$authenticated_user_id, $user_id, $limit, $offset]) ?? null;

        return $result ?? [];
    }

    public function getPostById(int $post_id): ?Post {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT
                post_id,
                user_id,
                reply_to_id,
                content,
                image_hash,
                status,
                scheduled_at,
                created_at,
                updated_at
            FROM
                posts 
            WHERE
                post_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "i", [$post_id]);

        return $result && count($result) > 0 ? $this->rawDataToPost($result[0]) : null;
    }

    public function delete(int $post_id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            DELETE FROM 
                posts
            WHERE
                post_id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute($query, "d", [$post_id]);

        if (!$result) {
            return false;
        }

        return true;
    }

    private function rawDataToPost(array $rawData): Post {
        return new Post(
            post_id: $rawData["post_id"],
            user_id: $rawData["user_id"],
            reply_to_id: $rawData["reply_to_id"],
            content: $rawData["content"],
            image_hash: $rawData["image_hash"],
            status: $rawData["status"],
            scheduled_at: $rawData["scheduled_at"],
            created_at: $rawData["created_at"],
            updated_at: $rawData["updated_at"],
        );
    }

}