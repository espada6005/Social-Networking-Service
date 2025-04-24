<?php

namespace Database\Migrations;

use Database\SchemaMigration;

class CreateUsersTable implements SchemaMigration {
    public function up(): array {
        // マイグレーションロジックをここに追加してください
        return [
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                profile_text VARCHAR(255) NULL,
                profile_image_hash VARCHAR(255) NULL,
                type ENUM('USER', 'GUEST', 'INFLUENCER') NOT NULL DEFAULT 'user',
                email_confirmed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];
    }

    public function down(): array {
        // ロールバックロジックを追加してください
        return [
            "DROP TABLE users"
        ];
    }
}