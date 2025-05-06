<?php

namespace Database\Migrations;

use Database\SchemaMigration;

class CreateFollowsTable implements SchemaMigration {
    public function up(): array {
        // マイグレーションロジックをここに追加してください
        return [
            "CREATE TABLE follows (
                follow_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                follower_id BIGINT UNSIGNED NOT NULL,
                followee_id BIGINT UNSIGNED NOT NULL,
                FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (followee_id) REFERENCES users(user_id) ON DELETE CASCADE
            )"
        ];
    }

    public function down(): array {
        // ロールバックロジックを追加してください
        return [
            "DROP TABLE follows"
        ];
    }
}