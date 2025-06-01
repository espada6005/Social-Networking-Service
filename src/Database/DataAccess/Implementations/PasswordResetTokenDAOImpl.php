<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\PasswordResetTokenDAO;
use Database\DatabaseManager;
use Models\PasswordResetToken;

class PasswordResetTokenDAOImpl implements PasswordResetTokenDAO {

    public function create(PasswordResetToken $passwordResetToken): bool {
        if ($passwordResetToken->getTokenId() !== null) {
            throw new \Exception('Cannot create a password reset token with an existing ID. id: ' . $passwordResetToken->getTokenId());
        }

        $mysqli = DatabaseManager::getMysqliConnection();

        $query = <<<QUERY
            INSERT INTO password_reset_tokens (
                user_id,
                token
            ) 
            VALUES(
                ?, 
                ?
            );
        QUERY;

        $result = $mysqli->prepareAndExecute(
            $query,
            'is',
            [
                $passwordResetToken->getUserId(),
                $passwordResetToken->getToken()
            ]
        );

        if (!$result) {
            return false;
        }

        $passwordResetToken->setTokenId($mysqli->insert_id);

        return true;
    }

    public function getByToken(string $token): ?PasswordResetToken {
        $mysqli = DatabaseManager::getMysqliConnection();
        $query = <<<QUERY
            SELECT
                token_id,
                user_id,
                token,
                created_at
            FROM
                password_reset_tokens
            WHERE
                token = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, 's', [$token])[0] ?? null;

        if ($result === null) return null;

        return $this->rawDataToPasswordResetToken($result);
    }

    public function getByUserId(int $userId): ?PasswordResetToken {
        $mysqli = DatabaseManager::getMysqliConnection();
        $query = <<<QUERY
            SELECT
                token_id,
                user_id,
                token,
                created_at
            FROM
                password_reset_tokens
            WHERE
                user_id = ?;
        QUERY;

        $result = $mysqli->prepareAndFetchAll($query, 'i', [$userId])[0] ?? null;

        if ($result === null) return null;

        return $this->rawDataToPasswordResetToken($result);
    }

    public function deleteByUserId(int $id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();
        $query = <<<QUERY
            DELETE FROM
                password_reset_tokens 
            WHERE
                user_id = ?;
        QUERY;
        $result = $mysqli->prepareAndExecute($query, 'i', [$id]);
        return $result;
    }

    // 30分が期限
    public function deleteExpired(): bool {
        $mysqli = DatabaseManager::getMysqliConnection();
        $query = <<<QUERY
            DELETE FROM
                password_reset_tokens 
            WHERE
                created_at < NOW() - INTERVAL 30 MINUTE;
        QUERY;
        $result = $mysqli->prepareAndExecute($query, '', []);

        return $result;
    }

    private function rawDataToPasswordResetToken(array $rawData): PasswordResetToken {
        return new PasswordResetToken(
            user_id: $rawData['user_id'],
            token: unpack('H*', $rawData['token'])[1],
            token_id: $rawData['id'],
            created_at: $rawData['created_at']
        );
    }

}
