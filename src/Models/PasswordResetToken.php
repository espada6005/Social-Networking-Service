<?php

namespace Models;

use Models\Interfaces\Model;
use Models\Traits\GenericModel;

class PasswordResetToken implements Model {
    use GenericModel;

    public function __construct(
        private int $user_id,
        private string $token,
        private?int $token_id = null,
        private ?string $created_at = null
    ) {}

    public function getTokenId(): ?int {
        return $this->token_id;
    }

    public function setTokenId(int $token_id): void {
        $this->token_id = $token_id;
    }

    public function getUserId(): int {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void {
        $this->user_id = $user_id;
    }

    public function getToken(): string {
        return $this->token;
    }

    public function setToken(string $token): void {
        $this->token = $token;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void {
        $this->created_at = $created_at;
    }

}
