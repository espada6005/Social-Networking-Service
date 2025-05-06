<?php

namespace Database\Migrations;

use Database\SchemaMigration;

class UpdateUsersTable implements SchemaMigration {
    public function up(): array {
        // マイグレーションロジックをここに追加してください
        return [
            "ALTER TABLE users CHANGE id user_id BIGINT UNSIGNED AUTO_INCREMENT"
        ];
    }

    public function down(): array {
        // ロールバックロジックを追加してください
        return [
            "ALTER TABLE users CHANGE user_id id BIGINT AUTO_INCREMENT"
        ];
    }
}