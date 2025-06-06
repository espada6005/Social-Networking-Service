<?php

namespace Helpers;

use Types\ValueType;

class ValidationHelper {
    public static function validateFields(array $fields, array $data): array {
        $validatedData = [];

        foreach ($fields as $field => $type) {
            if (!isset($data[$field]) || ($data)[$field] === "") {
                $validatedData[$field] = "入力してください";
            }

            $value = $data[$field];

            switch ($type) {
                case ValueType::STRING:
                    if (!self::validateString($value)) {
                        $validatedData[$field] = "1文字以上50文字以内で入力してください";
                    }
                    break;
                case ValueType::USERNAME:
                    if (!self::validateUsername($value)) {
                        $validatedData[$field] = "1文字以上20文字以内の半角英数字とアンダースコアのみを使用してください";
                    }
                    break;
                case ValueType::EMAIL:
                    if (!self::validateEmail($value)) {
                        $validatedData[$field] = "有効なメールアドレスを入力してください";
                    }
                    break;
                case ValueType::PASSWORD:
                    if (!self::validatePassword($value)) {
                        $validatedData[$field] = "有効なパスワードを入力してください";
                    }
                    break;
            }

        }

        return $validatedData;
    }
    public static function validateString(string $value): bool {
        return is_string($value) 
            && preg_replace("/\A[\p{Cc}\p{Cf}\p{Z}]++|[\p{Cc}\p{Cf}\p{Z}]++\z/u", "", $value)
            && strlen($value) >= 1
            && strlen($value) <= 50;
    }

    public static function validateEmail(string $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validatePassword(string $value): bool {
        return is_string($value) &&
                strlen($value) >= 8 && // 8文字以上
                strlen($value) <= 30 && // 30文字以下
                preg_match("/[A-Z]/", $value) && // 1文字以上の大文字
                preg_match("/[a-z]/", $value) && // 1文字以上の小文字
                preg_match("/\d/", $value) && // 1文字以上の数値
                preg_match("/[\W_]/", $value); // 1文字以上の特殊文字（アルファベット以外の文字）
    }   

    public static function validateUsername(string $value): bool {
        return is_string($value) &&
                preg_match("/^[a-zA-Z0-9_]+$/", $value) && // 半角英数字とアンダースコアのみを許可
                strlen($value) >= 1 && // 3文字以上
                strlen($value) <= 20; // 20文字以下
    }

    public static function integer($value, float $min = -INF, float $max = INF): int {
        // PHPには、データを検証する組み込み関数がある
        $value = filter_var($value, FILTER_VALIDATE_INT, ["min_range" => (int) $min, "max_range" => (int) $max]);

        // 結果がfalseの場合、フィルターは失敗
        if ($value === false) {
            throw new \InvalidArgumentException("The provided value is not a valid integer.");
        }

        // 値がすべてのチェックをパスしたら、そのまま返す
        return $value;
    }

    public static function validateImageType(string $type): bool {
        $allowedTypes = ["image/png", "image/jpeg", "image/gif"];
        return in_array($type, $allowedTypes);
    }

    public static function validateImageSize(int $size, int $min = 1, int $max = 1048576): bool {
        return $size >= $min && $size <= $max;
    }

}
