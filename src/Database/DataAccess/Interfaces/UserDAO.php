<?php

namespace Database\DataAccess\Interfaces;

use Models\User;

interface UserDAO {

    public function create(User $user, string $password): bool;
    public function getById(int $id): ?User;
    public function getByEmail(string $email): ?User;
    public function getByUsername(string $username): ?User;
    public function getGuestUser(): ?User;
    public function getHashedPasswordById(int $id): ?string;
    public function update(User $user): bool;
    public function updateEmailConfirmedAt(int $id): bool;
    public function updatePassword(int $id, string $password): bool;
    public function delete(int $id): bool;

}
