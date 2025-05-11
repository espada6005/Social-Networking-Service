<?php

namespace Database\DataAccess\Interfaces;

use Models\User;

interface UserDAO {

    public function create(User $user, string $password): bool;

    public function getById(int $user_id): ?User;

    public function getByEmail(string $email): ?User;

    public function getByUsername(string $username): ?User;

    public function getGuestUser(): ?User;

    public function getHashedPasswordById(int $user_id): ?string;

    public function update(User $user): bool;

    public function updateEmailConfirmedAt(int $id): bool;

    public function updatePassword(int $user_id, string $password): bool;
    
    public function delete(int $user_id): bool;

}
