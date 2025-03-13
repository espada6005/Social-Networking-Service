<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\UserDAO;
use Database\DatabaseManager;
use Models\User;

class UserDAOImpl implements UserDAO {

    public function create(User $user, string $password): bool {
        if ($user->getId() !== null) {
            throw new \Exception("Cannot create a computer part with as existing ID. id: {$user->getId()}");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO users (
                name, 
                username, 
                email, 
                password
            ) 
            VALUES (
                ?, 
                ?, 
                ?, 
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ssss",
            [
                $user->getName(),
                $user->getUsername(),
                $user->getEmail(),
                password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        if (!$result) {
            return false;
        }

        $user->setId($mysqli->insert_id);

        return true;
    }

    public function getById(int $id): ?User {
        $userRaw = $this->getRawById($id);

        if ($userRaw === null) {
            return null;
        }

        return $this->rawDataToUser($userRaw);
    }

    public function update(User $user): bool {
        if ($user->getId() === null) {
            throw new \Exception("User specified has no ID");
        }

        $current = $this->getById($user->getId());

        if ($current === null) {
            throw new \Exception("User {$user->getId()} does not exist.");
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            UPDATE 
                users
            SET 
                name = ?, 
                username = ?, 
                profile_text = ?, 
                profile_image_hash = ?
            WHERE 
                id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            "ssssi",
            [
                $user->getName(),
                $user->getUsername(),
                $user->getProfileText(),
                $user->getProfileImageHash(),
                $user->getId(),
            ],
        );

        if (!$result) {
            return false;
        }

        return true;
    }

    public function delete(int $id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            DELETE FROM
                users
            WHERE 
                id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute($query, 'i', [$id]);
        return $result;
    }

    private function getRawById(int $id): ?array {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            SELECT 
                id, 
                name, 
                username, 
                email, 
                password, 
                profile_text, 
                profile_image_hash, 
                type, 
                email_confirmed_at, 
                created_at, 
                updated_at 
            FROM 
                users
            WHERE
                id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "i", [$id])[0] ?? null;

        if ($result === null) {
            return null;
        }

        return $result;
    }

    private function rawDataToUser(array $rawData): User {
        return new User(
            id: $rawData["id"],
            name: $rawData["name"],
            username: $rawData["username"],
            email: $rawData["email"],
            type: $rawData["type"],
            profile_text: $rawData["profile_text"],
            profile_image_hash: $rawData["profile_image_hash"],
            email_confirmed_at: $rawData["email_confirmed_at"],
            created_at: $rawData["created_at"],
            updated_at: $rawData["updated_at"],
        );
    }

}