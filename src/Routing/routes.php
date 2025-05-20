<?php

use Database\DataAccess\DAOFactory;
use Exceptions\AuthenticationFailureException;
use Helpers\Authenticate;
use Helpers\MailSender;
use Helpers\Settings;
use Helpers\ValidationHelper;
use Models\User;
use Response\FlashData;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\JSONRenderer;
use Response\Render\RedirectRenderer;
use Routing\Route;
use Types\ValueType;

require_once("../src/Constants/file_constants.php");

return [
    // トップページ
    "" => Route::create("", function (): HTTPRenderer {
        return new HTMLRenderer("pages/top", []);
    })->setMiddleware(["guest"]),
    // ゲストログイン
    "guest/login" => Route::create("guest/login", function (): HTTPRenderer {
        try {
            $userDao = DAOFactory::getUserDAO();

            // ゲストユーザーを取得
            $guestUser = $userDao->getGuestUser();

            if (!$guestUser === null) {
                throw new Exception("ゲストユーザーが取得できませんでした");
            }
            // ゲストユーザーとしてログイン
            Authenticate::loginAsUser($guestUser);

            return new JSONRenderer(["status" => "success", "redirectUrl" => "timeline"]);

        } catch (\Exception $e) {
            return new JSONRenderer(["status" => "error", "message" => $e->getMessage()]);
        }
    })->setMiddleware(["guest"]),
    // ログイン
    "form/login" => Route::create("form/login", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            // 入力値検証
            $fieldErrors = ValidationHelper::validateFields([
                "email" => ValueType::EMAIL,
                "password" => ValueType::PASSWORD,
            ], $_POST);

            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // ユーザー認証
            $user = Authenticate::authenticate($_POST["email"], $_POST["password"]);

            return new JSONRenderer(["status" => "success", "redirectUrl" => "timeline"]);
        } catch (AuthenticationFailureException $e) {
            return new JSONRenderer(["status" => "error", "message" => $e->getMessage()]);
        } catch (\Exception $e) {
            return new JSONRenderer(["status" => "error", "message" => $e->getMessage()]);
        }
    })->setMiddleware(["guest"]),
    // ログアウト
    "logout" => Route::create("logout", function (): HTTPRenderer {
        Authenticate::logoutUser();
        return new RedirectRenderer("");
    })->setMiddleware(["auth"]),
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
                "id" => hash_hmac("sha256", $user->getUserId(), Settings::env("SECRET_KEY")),
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
    // メール送信後
    "verify/resend" => Route::create("verify/resend", function (): HTTPRenderer {
        return new HTMLRenderer("pages/verify_resend", []);
    })->setMiddleware(["auth"]),
    // メール認証
    "verify/email" => Route::create("verify/email", function (): HTTPRenderer {
        try {
            // ログイン済みユーザーを取得
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            $hashedUserId = hash_hmac("sha256", $authenticatedUser->getUserId(), Settings::env("SECRET_KEY"));
            $hashedEmail = hash_hmac("sha256", $authenticatedUser->getEmail(), Settings::env("SECRET_KEY"));

            $expectedHashedId = $_GET["id"];
            $expectedHashedEmail = $_GET["user"];

            if (!hash_equals($hashedUserId, $expectedHashedId) || !hash_equals($hashedEmail, $expectedHashedEmail)) {
                throw new Exception("Invalid verification link");
            }

            $userDao = DAOFactory::getUserDAO();
            $result = $userDao->updateEmailConfirmedAt($authenticatedUser->getUserId());

            if (!$result) {
                throw new Exception("メール認証に失敗しました");
            }

            FlashData::setFlashData("success", "メール認証が完了しました");

            return new RedirectRenderer("profile");
        } catch (\Exception $e) {
            error_log($e->getMessage());
            FlashData::setFlashData("error", "メール認証に失敗しました");
            return new RedirectRenderer("verify/resend");
        }
    })->setMiddleware(["auth", "signature"]),
    // タイムライン
    "timeline" => Route::create("timeline", function (): HTTPRenderer {
        return new HTMLRenderer("pages/timeline", []);
    })->setMiddleware(["auth", "verify"]),
    // ユーザープロフィール
    "profile" => Route::create("profile", function (): HTTPRenderer {
        return new HTMLRenderer("pages/profile", []);
    })->setMiddleware(["auth", "verify"]),
    // プロフィール情報取得
    "profile/init" => Route::create("profile/init", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $username = $_POST["user"];

            if ($username === "") {
                $user = Authenticate::getAuthenticatedUser();
            } else {
                $userDao = DAOFactory::getUserDAO();
                $user = $userDao->getByUsername($username);
            }

            if ($user === null) {
                return new JSONRenderer(["user" => null]);
            }

            $followDao = DAOFactory::getFollowDAO();
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            return new JSONRenderer([
                "status" => "success",
                "user" => [
                    "isLoggedInUser" => intval($user->getUsername() === $authenticatedUser->getUsername()),
                    "isFollowee" => intval($followDao->isFollowee($authenticatedUser->getUserId(), $user->getUserId())),
                    "isFollower" => intval($followDao->isFollower($authenticatedUser->getUserId(), $user->getUserId())),
                    "name" => $user->getName(),
                    "username" => $user->getUsername(),
                    "profileText" => $user->getProfileText(),
                    "profileImagePath" => $user->getProfileImageHash() ?
                        PROFILE_IMAGE_FILE_DIR . $user->getProfileImageHash() :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "userType" => $user->getType(),
                    "profileImageType" => $user->getProfileImageHash() === null ? "default" : "custom",
                    "followeeCount" => $followDao->getFolloweeCount($user->getUserId()),
                    "followerCount" => $followDao->getFollowerCount($user->getUserId()),
                ],
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => $e->getMessage()]);
        }
    })->setMiddleware(["auth", "verify"]),
];
