<?php

use Database\DataAccess\DAOFactory;
use Helpers\Authenticate;
use Helpers\MailSender;
use Helpers\Settings;
use Helpers\ValidationHelper;
use Models\User;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\JSONRenderer;
use Routing\Route;
use Types\ValueType;

return [
     // トップページ
    "" => Route::create("", function (): HTTPRenderer {
        return new HTMLRenderer("pages/top", []);
    })->setMiddleware(["guest"]),
    // ユーザー登録
    "form/register" => Route::create("form/register", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $requiredFields = [
                "name" => ValueType::STRING,
                "username" => ValueType::STRING,
                "email" => ValueType::EMAIL,
                "password" => ValueType::PASSWORD,
                "confirm_password" => ValueType::PASSWORD,
            ];

            $userDao = DAOFactory::getUserDAO();

            // 入力値検証
            $fieldErrors = ValidationHelper::validateFields($requiredFields, $_POST);

            if ($userDao->getByEmail($_POST["email"])) {
                $fieldErrors["email"] = "メールアドレスは既に使われています";
            }

            if (
                !array_key_exists("password", $fieldErrors) &&
                !array_key_exists("confirm_password", $fieldErrors) &&
                $_POST["password"] !== $_POST["confirm_password"]
            ) {
                $fieldErrors["password"] = "パスワードが一致しません";
                $fieldErrors["confirm_password"] = "パスワードが一致しません";
            }

            // 入力値に問題がある場合、JSONレスポンスを返す
            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // 新しいUserを作成
            $user = new User(
                name: $_POST["name"],
                username: $_POST["username"],
                email: $_POST["email"],
            );

            // ユーザーを登録
            $success = $userDao->create($user, $_POST["password"]);

            if (!$success) {
                throw new Exception("登録に失敗しました");
            }

            // ログイン
            Authenticate::loginAsUser($user);

            // メール認証用のURLを生成
            $lats = 1800;
            $params = [
                "id" => hash_hmac("sha256", $user->getId(), Settings::env("SECRET_KEY")),
                "user" => hash_hmac("sha256", $user->getEmail(), Settings::env("SECRET_KEY")),
                "expiration" => time() + $lats,
            ];
            $signedUrl = Route::create("verify/email", function () {})->getSignedURL($params);

            $sendResulet = MailSender::sendVerificationEmail(
                $signedUrl,
                $user->getEmail(),
                $user->getName()
            );

            if (!$sendResulet) {
                throw new Exception("メールの送信に失敗しました");
            }

            return new JSONRenderer(["status" => "success", "redirectUrl" => "/verify/resend"]);
        } catch (\Exception $e) {
            return new JSONRenderer(["status" => "error", "message" => $e->getMessage()]);
        }
    })->setMiddleware(["guest"]),
];
