<?php

namespace Database\DataAccess\Interfaces;

use Models\PasswordResetToken;

interface PasswordResetTokenDAO {
    public function create(PasswordResetToken $passwordResetToken): bool;

    public function getByToken(string $token) : ?PasswordResetToken;

    public function getByUserId(int $userId) : ?PasswordResetToken;

    public function deleteByUserId(int $id) : bool;

    public function deleteExpired() : bool;
}
