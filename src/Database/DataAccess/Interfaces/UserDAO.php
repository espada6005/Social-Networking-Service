<?php

namespace Database\DataAccess\Interfaces;

use Models\User;

interface UserDAO {

    public function create(User $user, string $password): bool;
    public function getById(int $id): ?User;
    public function update(User $user): bool;
    public function delete(int $id): bool;

}
