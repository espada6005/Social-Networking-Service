<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\MessageDAO;
use Database\DatabaseManager;
use Models\Message;

class MessageDAOImpl implements MessageDAO {
    
    public function create(Message $message): bool {
        
        if ($message->getMessageId() !== null) {
            throw new \Exception("メッセージを作成できません");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO messages (
                from_user_id,
                to_user_id,
                content
            )
            VALUES (
                ?,
                ?,
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "dds",
            [
                $message->getFromUserId(),
                $message->getToUserId(),
                $message->getContent(),
            ],
        );

        if (!$result) {
            return false;
        }

        $message->setMessageId($mysqli->insert_id);

        return true;
    }

        public function getChatUsers(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                users.user_id,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type
            FROM 
                messages
            INNER JOIN 
                users ON users.user_id = messages.from_user_id 
                OR users.user_id = messages.to_user_id
            WHERE 
                messages.from_user_id = ? 
                OR messages.to_user_id = ?
            GROUP BY 
                users.username
            HAVING 
                users.user_id <> ?
            ORDER BY 
                MAX(messages.created_at) DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iiiii", [$user_id, $user_id, $user_id, $limit, $offset]);

        return $result ?? [];
    }

    public function getChatMessages(int $user_id, int $chat_user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                messages.from_user_id,
                messages.content
            FROM 
                messages
            WHERE 
                (messages.from_user_id = ? AND messages.to_user_id = ?)
                OR (messages.from_user_id = ? AND messages.to_user_id = ?)
            ORDER BY 
                messages.created_at DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll(
            $query,
            "iiiiii",
            [$user_id, $chat_user_id, $chat_user_id, $user_id, $limit, $offset],
        );

        return $result ?? [];
    }

}