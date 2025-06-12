<?php

use Database\DataAccess\DAOFactory;
use Exceptions\AuthenticationFailureException;
use Helpers\Authenticate;
use Helpers\ImageHelper;
use Helpers\MailSender;
use Helpers\Settings;
use Helpers\ValidationHelper;
use Models\PasswordResetToken;
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
    "form/user/register" => Route::create("form/user/register", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $userDao = DAOFactory::getUserDAO();

            // 入力値検証
            $fieldErrors = ValidationHelper::validateFields([
                "name" => ValueType::STRING,
                "username" => ValueType::USERNAME,
                "email" => ValueType::EMAIL,
                "password" => ValueType::PASSWORD,
                "confirm-password" => ValueType::PASSWORD,
            ], $_POST);

            if ($userDao->getByEmail($_POST["email"])) {
                $fieldErrors["email"] = "メールアドレスは既に使われています";
            }

            if (
                !array_key_exists("password", $fieldErrors) &&
                !array_key_exists("confirm-password", $fieldErrors) &&
                $_POST["password"] !== $_POST["confirm-password"]
            ) {
                $fieldErrors["password"] = "パスワードが一致しません";
                $fieldErrors["confirm-password"] = "パスワードが一致しません";
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
                return new JSONRenderer(["status" => "error", "message" => "ユーザー登録に失敗しました"]);
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
                return new JSONRenderer(["status" => "error", "message" => "メールの送信に失敗しました"]);
            }

            return new JSONRenderer(["status" => "success", "redirectUrl" => "/verify/resend"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
    })->setMiddleware(["guest"]),
    // ユーザー削除確認
    "user/delete" => Route::create("user/delete", function (): HTTPRenderer {
        try {
            // 認証済みユーザーを取得
            $user = Authenticate::getAuthenticatedUser();

            return new HTMLRenderer("pages/user_delete", ["userId" => $user->getUserId()]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new RedirectRenderer("timeline");
        }
    })->setMiddleware(["auth", "verify"]),
    // ユーザー削除
    "form/user/delete" => Route::create("form/user/delete", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            // 認証済みユーザーを取得
            $user = Authenticate::getAuthenticatedUser();
            if ($user->getUserId() != $_POST["user_id"]) {
                return new JSONRenderer(["status" => "error", "message" => "不正なユーザー削除リクエストです"]);
            }

            $userDao = DAOFactory::getUserDAO();
            // プロフィール画像があれば削除
            if ($user->getProfileImageHash() !== null) {
                ImageHelper::deleteProfileImage($user->getProfileImageHash());
            }
            // ユーザーを削除
            $success = $userDao->delete($user->getUserId());
            
            if (!$success) {
                return new JSONRenderer(["status" => "error", "message" => "ユーザー削除に失敗しました"]);
            }

            // ログアウト
            Authenticate::logoutUser();

            return new JSONRenderer(["status" => "success", "redirectUrl" => "/"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // 認証メール送信後
    "verify/resend" => Route::create("verify/resend", function (): HTTPRenderer {
        return new HTMLRenderer("pages/verify_resend", []);
    })->setMiddleware(["auth"]),
    // メール認証再送信
    "form/verify/resend" => Route::create("form/verify/resend", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $user = Authenticate::getAuthenticatedUser();
            $lats = 1800;
            $params = [
                "id" => hash_hmac("sha256", $user->getUserId(), Settings::env("SECRET_KEY")),
                "user" => hash_hmac("sha256", $user->getEmail(), Settings::env("SECRET_KEY")),
                "expiration" => time() + $lats,
            ];
            $signedUrl = Route::create("verify/email", function () {})->getSignedURL($params);

            $result = MailSender::sendVerificationEmail(
                $signedUrl,
                $user->getEmail(),
                $user->getName()
            );

            if (!$result) {
                return new JSONRenderer(["status" => "error", "message" => "メールの送信に失敗しました"]);
            }

            return new JSONRenderer(["status" => "success", "redirectUrl" => "/verify/resend"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
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

            return new RedirectRenderer("profile?user=" . $authenticatedUser->getUsername());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            FlashData::setFlashData("error", "メール認証に失敗しました");
            return new RedirectRenderer("verify/resend");
        }
    })->setMiddleware(["auth", "signature"]),
    // パスワード忘れ
    "password/forgot" => Route::create("password/forgot", function (): HTTPRenderer {
        return new HTMLRenderer("pages/password_forgot", []);
    })->setMiddleware(["guest"]),
    // パスワードリセットメール送信
    "form/password/forgot" => Route::create("form/password/forgot", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            // 入力値検証
            $fieldErrors = ValidationHelper::validateFields([
                "email" => ValueType::EMAIL,
            ], $_POST);

            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            $userDao = DAOFactory::getUserDAO();
            $user = $userDao->getByEmail($_POST["email"]);

            if ($user === null) {
                $fieldErrors["email"] = "メールアドレスが見つかりません";
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // パスワードリセット用のURLを生成
            $lats = 1800;
            $params = [
                "expiration" => time() + $lats,
            ];

            $route = Route::create("password/reset", function () {});
            $signedUrl = $route->getSignedURL($params);

            // メール送信
            $result = MailSender::sendPasswordResetEmail($signedUrl, $user->getEmail(), $user->getName());

            if (!$result) {
                return new JSONRenderer(["status" => "error", "message" => "メールの送信に失敗しました"]);
            }

            $signature = $route->getSignature($signedUrl);

            $passwordResetToken = new PasswordResetToken(
                user_id: $user->getUserId(),
                token: pack("H*", $signature)
            );

            $passwordResetDao = DAOFactory::getPasswordResetDAO();
            $success = $passwordResetDao->create($passwordResetToken);

            if (!$success) {
                return new JSONRenderer(["status" => "error", "message" => "パスワードリセットトークンの保存に失敗しました"]);
            }

            return new JSONRenderer(["status" => "success", "message" => "パスワードリセット用のメールを送信しました"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
    })->setMiddleware(["guest"]),
    // パスワードリセット
    "password/reset" => Route::create("password/reset", function (): HTTPRenderer {
        try {
            $passwordResetDao = DAOFactory::getPasswordResetDAO();
            $passwordResetToken = $passwordResetDao->getByToken(pack("H*", $_GET["signature"]));

            if ($passwordResetToken === null) {
                return new RedirectRenderer("password/forgot");
            }

            return new HTMLRenderer("pages/password_reset", ["userId" => $passwordResetToken->getUserId()]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new RedirectRenderer("password/forgot");
        }
    })->setMiddleware(["guest", "signature"]),
    // パスワード更新
    "form/password/reset" => Route::create("form/password/reset", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            // 入力値検証
            $fieldErrors = ValidationHelper::validateFields([
                "password" => ValueType::PASSWORD,
                "confirm-password" => ValueType::PASSWORD,
            ], $_POST);

            if (!array_key_exists("password", $fieldErrors) && !array_key_exists("confirm-password", $fieldErrors) &&
                $_POST["password"] !== $_POST["confirm-password"]) {
                $fieldErrors["password"] = "パスワードが一致しません";
                $fieldErrors["confirm-password"] = "パスワードが一致しません";
            }

            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // ユーザーを取得
            $userDao = DAOFactory::getUserDAO();
            $user = $userDao->getById($_POST["user_id"]);

            if ($user === null) {
                return new JSONRenderer(["status" => "error", "message" => "ユーザーが見つかりません"]);
            }

            // パスワードを更新
            $success = $userDao->updatePassword($user->getUserId(), $_POST["password"]);

            if (!$success) {
                return new JSONRenderer(["status" => "error", "message" => "パスワードの更新に失敗しました"]);
            }

            // パスワードリセットトークンを削除
            $passwordResetDao = DAOFactory::getPasswordResetDAO();
            $passwordResetDao->deleteByUserId($_POST["user_id"]);

            return new JSONRenderer(["status" => "success", "redirectUrl" => "/"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
    })->setMiddleware(["guest"]),
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
    // プロフィール更新
    "form/profile/update" => Route::create("form/profile/update", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $fieldErrors = ValidationHelper::validateFields([
                "name" => ValueType::STRING,
                "username" => ValueType::USERNAME,
            ], $_POST);

            $sameUsernameUser = DAOFactory::getUserDAO()->getByUsername($_POST["username"]);
            if (isset($fieldErrors["username"]) && $sameUsernameUser !== null && $sameUsernameUser->getUserId() !== Authenticate::getAuthenticatedUser()->getUserId()) {
                $fieldErrors["username"] = "このユーザー名は既に使用されています";
            }

            if ($_POST["profile-text"] !== null && mb_strlen($_POST["profile-text"]) > 160) {
                $fieldErrors["profile-text"] = "プロフィールは160文字以内で入力してください";
            }
            
            $profileImageType = $_POST["profile-image-type"];
            $fileError = $_FILES["profile-image"]["error"];
            if ($profileImageType === "custom") {
                if ($fileError === UPLOAD_ERR_OK) {
                    if (!ValidationHelper::validateImageType($_FILES["profile-image"]["type"])) {
                        $fieldErrors["profile-image"] =
                            "ファイル形式が不適切です。JPG, JPEG, PNG, GIFのファイルが設定可能です。";
                    } else if (!ValidationHelper::validateImageSize($_FILES["profile-image"]["size"])) {
                        $fieldErrors["profile-image"] =
                            "ファイルが大きすぎます。";
                    }
                } else if ($fileError !== UPLOAD_ERR_NO_FILE) {
                    $fieldErrors["profile-image"] =
                        "ファイルサイズ等の問題によりこの画像は設定できません。";
                }
            }

            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // 認証済みユーザーを取得
            $user = Authenticate::getAuthenticatedUser();

             // プロフィール画像を保存
            if ($profileImageType === "custom" && $fileError === UPLOAD_ERR_OK) {
                $imageHash = ImageHelper::saveProfileImage(
                    $_FILES["profile-image"]["tmp_name"],
                    ImageHelper::imageTypeToExtension($_FILES["profile-image"]["type"]),
                    $user->getUsername(),
                );
            } else if ($profileImageType === "custom") {
                $imageHash = $user->getProfileImageHash();
            } else {
                $imageHash = null;
            }

            // 元のプロフィール画像が不要になる場合は削除
            // 元々画像を設定されているケースで、デフォルト画像を使用or新しい画像を設定した場合
            if ($user->getProfileImageHash() !== null) {
                if ($profileImageType === "default" || ($profileImageType === "custom" && $fileError === UPLOAD_ERR_OK)) {
                    ImageHelper::deleteProfileImage($user->getProfileImageHash());
                }
            }

            // ユーザーのデータを更新
            $user->setName($_POST["name"]);
            $user->setUsername($_POST["username"]);
            $user->setProfileText($_POST["profile-text"]);
            $user->setProfileImageHash($imageHash);

            $userDao = DAOFactory::getUserDAO();
            $userDao->update($user);

            return new JSONRenderer(["status" => "success", "message" => "プロフィールが更新されました"]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // フォロワー一覧
    "followers" => Route::create("/followers", function(): HTTPRenderer {
        return new HTMLRenderer("pages/followers", []);
    })->setMiddleware(["auth", "verify"]),
    // フォロワー一覧取得
    "followers/init" => Route::create("followers/init", function(): HTTPRenderer {
        $resBody = ["success" => true];

        try {
            $username = $_POST["user"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($username === "") {
                $user = Authenticate::getAuthenticatedUser();
            } else {
                $userDao = DAOFactory::getUserDAO();
                $user = $userDao->getByUsername($username);
            }

            if ($user === null) {
                $resBody["followers"] = null;
            } else {
                $followDao = DAOFactory::getFollowDAO();

                $limit = $_POST["limit"] ?? 30;
                $offset = $_POST["offset"] ?? 0;
                $followers = $followDao->getFollowers($user->getUserId(), $limit, $offset);

                for ($i = 0; $i < count($followers); $i++) {
                    $followers[$i] = [
                        "name" => $followers[$i]["name"],
                        "username" => $followers[$i]["username"],
                        "profileImagePath" => $followers[$i]["profile_image_hash"] ?
                            PROFILE_IMAGE_FILE_DIR . $followers[$i]["profile_image_hash"] :
                            PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                        "profilePath" => "/user?un=" . $followers[$i]["username"],
                        "userType" => $followers[$i]["type"],
                    ];
                }

                $resBody["followers"] = $followers;
            }

            return new JSONRenderer($resBody);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resBody["success"] = false;
            $resBody["error"] = "エラーが発生しました。";
            return new JSONRenderer($resBody);
        }
    })->setMiddleware(["auth", "verify"]),
];
