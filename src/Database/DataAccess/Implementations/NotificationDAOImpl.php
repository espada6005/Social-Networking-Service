<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\NotificationDAO;
use Database\DatabaseManager;
use Models\Notification;

class NotificationDAOImpl implements NotificationDAO {
    
        public function create(Notification $notification): bool {
        if ($notification->getNotificationId() !== null) {
            throw new \Exception("このNotificationデータを作成することはできません。: " . $notification->toString());
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO notifications (
                from_user_id,
                to_user_id,
                source_id,
                type,
                is_read
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "dddsd",
            [
                $notification->getFromUserId(),
                $notification->getToUserId(),
                $notification->getSourceId(),
                $notification->getType(),
                $notification->getIsRead(),
            ],
        );

        if (!$result) {
            return false;
        }
        
        $notification->setNotificationId($mysqli->insert_id);

        return true;
    }

    public function updateIsRead(Notification $notification): bool {
        if ($notification->getNotificationId() === null) {
            throw new \Exception("更新処理できません");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            UPDATE 
                notifications
            SET 
                is_read = ?
            WHERE 
                notification_id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ii",
            [
                $notification->getIsRead(),
                $notification->getNotificationId(),
            ],
        );

        if (!$result) {
            return false;
        }

        return true;
    }

    public function getUserNotifications(int $user_id, int $limit, int $offset): array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                notifications.notification_id,
                notifications.type AS notification_type,
                notifications.source_id,
                notifications.is_read,
                users.name,
                users.username,
                users.profile_image_hash,
                users.type AS user_type
            FROM 
                notifications
            INNER JOIN 
                users ON users.user_id = notifications.from_user_id
            WHERE 
                notifications.to_user_id = ?
            ORDER BY 
                notifications.created_at DESC
            LIMIT ?
            OFFSET ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "iii", [$user_id, $limit, $offset]);

        return $result ?? [];
    }

    public function getNotificationById(int $notification_id): ?Notification {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                notifications.notification_id,
                notifications.from_user_id,
                notifications.to_user_id,
                notifications.source_id,
                notifications.type,
                notifications.is_read,
                notifications.created_at,
                notifications.updated_at
            FROM 
                notifications
            WHERE 
                notifications.notification_id = ?
            LIMIT 1;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "i", [$notification_id]);

        return $result ? $this->rawDataToNotification($result[0]) : null;
    }

    public function getUserUnreadNotificationCount(int $user_id): int {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                COUNT(*) AS count
            FROM 
                notifications
            WHERE 
                notifications.to_user_id = ?
            AND notifications.is_read = FALSE;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "i", [$user_id]);

        return $result[0]["count"] ?? 0;
    }

    private function rawDataToNotification(array $rawData): Notification {
        return new Notification(
            notification_id: $rawData["notification_id"],
            from_user_id: $rawData["from_user_id"],
            to_user_id: $rawData["to_user_id"],
            source_id: $rawData["source_id"],
            type: $rawData["type"],
            is_read: $rawData["is_read"],
            created_at: $rawData["created_at"],
            updated_at: $rawData["updated_at"],
        );
    }

}