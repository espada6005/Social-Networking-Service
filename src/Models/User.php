<?php

namespace Models;

use Models\Interfaces\Model;
use Models\Traits\GenericModel;

class User implements Model {
    
    use GenericModel;

    private const TYPE_VALUES = ["USER"];

    public function __construct(
        private string $name,
        private string $username,
        private string $email,
        private string $type = "user",
        private ?int $id = null,
        private ?string $profile_text = null,
        private ?string $profile_image_hash = null,
        private ?string $email_confirmed_at = null,
        private ?string $created_at = null,
        private ?string $updated_at = null
    ) {}
    
    public function getId(): ?int {
        return $this->id;
    }

    public function setId(int $user_id): void {
        $this->id = $user_id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getProfileText(): ?string {
        return $this->profile_text;
    }

    public function setProfileText(string $profile_text): void {
        $this->profile_text = $profile_text;
    }

    public function getProfileImageHash(): ?string {
        return $this->profile_image_hash;
    }

    public function setProfileImageHash(?string $profile_image_hash): void {
        $this->profile_image_hash = $profile_image_hash;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function setType(string $type): void {
        if (in_array($type, self::TYPE_VALUES)) {
            $this->type = $type;
        }
    }

    public function getEmailConfirmedAt(): ?string {
        return $this->email_confirmed_at;
    }

    public function setEmailConfirmedAt(string $email_confirmed_at): void {
        $this->email_confirmed_at = $email_confirmed_at;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $updated_at): void {
        $this->updated_at = $updated_at;
    }

}
