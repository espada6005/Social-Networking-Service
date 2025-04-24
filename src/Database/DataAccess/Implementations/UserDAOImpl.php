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

    public function getByEmail(string $email): ?User {
        $userRaw = $this->getRawByEmail($email);

        if ($userRaw === null) {
            return null;
        }

        return $this->rawDataToUser($userRaw);
    }

    public function getByUsername(string $username): ?User {
        $userRaw = $this->getRwaByUsername($username);

        if ($userRaw === null) {
            return null;
        }

        return $this->rawDataToUser($userRaw);
    }

    public function getGuestUser(): ?User {
        $userRaw = $this->getRawGuestUser();

        if ($userRaw === null) {
            return null;
        }

        return $this->rawDataToUser($userRaw);
    }

    public function getHashedPasswordById(int $id): ?string {
        return $this->getRawById($id)["password"] ?? null;
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

    public function updateEmailConfirmedAt(int $id): bool {
        $mysqwli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            UPDATE 
                users
            SET 
                email_confirmed_at = NOW()
            WHERE 
                id = ?;
        QUERY;

        $result = $mysqwli->prepareAndExecute($query, "i", [$id]);

        return $result;
    }

    public function updatePassword(int $id, string $password): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            UPDATE 
                users
            SET 
                password = ?
            WHERE 
                id = ?;
        QUERY;

        $result = $mysqli->prepareAndExecute($query,"si", [
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);

        return $result;
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

    private function getRawByEmail(string $email): ?array {
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
                email = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "s", [$email])[0] ?? null;

        if ($result === null) {
            return null;
        }
        
        return $result;
    }

    private function getRwaByUsername(string $username): ?array {
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
                username = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "s", [$username])[0] ?? null;

        if ($result === null) {
            return null;
        }

        return $result;
    }

    private function getRawGuestUser(): ?array {
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
                type = "guest";
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, "", [])[0];

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