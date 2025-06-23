<?php

use Database\DataAccess\DAOFactory;
use Exceptions\AuthenticationFailureException;
use Helpers\Authenticate;
use Helpers\DateTimeHelper;
use Helpers\ImageHelper;
use Helpers\MailSender;
use Helpers\Settings;
use Helpers\ValidationHelper;
use Models\Follow;
use Models\Like;
use Models\Notification;
use Models\PasswordResetToken;
use Models\Post;
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

            return new  RedirectRenderer("timeline");
        } catch (\Exception $e) {
            error_log($e->getMessage());
            FlashData::setFlashData("error", "ゲストログインに失敗しました");
            return new RedirectRenderer("/");
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
                "name" => ValueType::NAME,
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
                "name" => ValueType::NAME,
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
    // フォロー
    "follow" => Route::create("follow", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $username = $_POST["user"];
            if ($username === "") {
                throw new Exception("パラメータが不適切です。");
            }

            $userDao = DAOFactory::getUserDAO();
            $user = $userDao->getByUsername($username);
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($user === null) {
                throw new Exception("フォロー対象のユーザーが存在しません。");
            } else if ($user->getUserId() === $authenticatedUser->getUserId()) {
                throw new Exception("フォロー対象のユーザーが不適切です。");
            }

            $followDao = DAOFactory::getFollowDAO();
            $isFollowee = $followDao->isFollowee($authenticatedUser->getUserId(), $user->getUserId());
            if ($isFollowee) {
                throw new Exception("既にフォローしています。");
            }

            $follow = new Follow(
                follower_id: $authenticatedUser->getUserId(),
                followee_id: $user->getUserId(),
            );
            $result = $followDao->create($follow);
            if (!$result) {
                throw new Exception("フォロー処理に失敗しました。");
            }

            $notification = new Notification(
                from_user_id: $authenticatedUser->getUserId(),
                to_user_id: $user->getUserId(),
                type: "FOLLOW",
            );
            $notificationDao = DAOFactory::getNotificationDAO();
            $result = $notificationDao->create($notification);
            if (!$result) {
                throw new Exception("通知作成処理に失敗しました。");
            }

            return new JSONRenderer(["status" => "success"]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // フォロー解除
    "unfollow" => Route::create("unfollow", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $username = $_POST["user"];
            if ($username === "") {
                throw new Exception("パラメータが不適切です。");
            }

            $userDao = DAOFactory::getUserDAO();
            $user = $userDao->getByUsername($username);
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($user === null) {
                throw new Exception("アンフォロー対象のユーザーが存在しません。");
            } else if ($user->getUserId() === $authenticatedUser->getUserId()) {
                throw new Exception("アンフォロー対象のユーザーが不適切です。");
            }

            $followDao = DAOFactory::getFollowDAO();
            $isFollowee = $followDao->isFollowee($authenticatedUser->getUserId(), $user->getUserId());
            if (!$isFollowee) {
                throw new Exception("現在フォローしているユーザーではありません。");
            }

            $result = $followDao->delete($authenticatedUser->getUserId(), $user->getUserId());
            if (!$result) {
                throw new Exception("アンフォロー処理に失敗しました。");
            }

            return new JSONRenderer(["status" => "success"]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // フォロワー一覧
    "followers" => Route::create("followers", function(): HTTPRenderer {
        return new HTMLRenderer("pages/followers", []);
    })->setMiddleware(["auth", "verify"]),
    // フォロワー一覧取得
    "followers/init" => Route::create("followers/init", function(): HTTPRenderer {
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
                $followers = null;
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
                        "profilePath" => "/profile?user=" . $followers[$i]["username"],
                        "userType" => $followers[$i]["type"],
                    ];
                }
            }

            return new JSONRenderer(["status" => "success", "followers" => $followers]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // フォロー一覧
    "followees" => Route::create("followees", function(): HTTPRenderer {
        return new HTMLRenderer("pages/followees", []);
    })->setMiddleware(["auth", "verify"]),
    // フォロー一覧取得
    "followees/init" => Route::create("followees/init", function(): HTTPRenderer {
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
                $followees = null;
            } else {
                $followDao = DAOFactory::getFollowDAO();

                $limit = $_POST["limit"] ?? 30;
                $offset = $_POST["offset"] ?? 0;
                $followees = $followDao->getFollowees($user->getUserId(), $limit, $offset);

                for ($i = 0; $i < count($followees); $i++) {
                    $followees[$i] = [
                        "name" => $followees[$i]["name"],
                        "username" => $followees[$i]["username"],
                        "profileImagePath" => $followees[$i]["profile_image_hash"] ?
                            PROFILE_IMAGE_FILE_DIR . $followees[$i]["profile_image_hash"] :
                            PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                        "profilePath" => "/profile?user=" . $followees[$i]["username"],
                        "userType" => $followees[$i]["type"],
                    ];
                };
            }

            return new JSONRenderer(["status" => "success", "followees" => $followees]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポスト作成
    "post/create" => Route::create("post/create", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $user = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();

            // 入力値検証
            if (!in_array($_POST["type"], ["create", "draft", "schedule"])) {
                throw new Exception("リクエストデータが不適切です。");
            }

            if ($_POST["post-content"] !== null && mb_strlen($_POST["post-content"]) > 140) {
                $fieldErrors["post-content"] = "投稿内容は140文字以内で入力してください";
            }

            $postImageUploaded = $_FILES["post-image"]["error"] === UPLOAD_ERR_OK;
            if ($postImageUploaded) {
                if (!ValidationHelper::validateImageType($_FILES["post-image"]["type"])) {
                    $fieldErrors["post-image"] =
                        "ファイル形式が不適切です。JPG, JPEG, PNG, GIFのファイルが設定可能です。";
                } else if (!ValidationHelper::validateImageSize($_FILES["post-image"]["size"])) {
                    $fieldErrors["post-image"] =
                        "ファイルが大きすぎます。";
                }
            }

            if ($_POST["type"] === "schedule") {
                if ($_POST["post-scheduled-at"] === null || !ValidationHelper::validateDateTime($_POST["post-scheduled-at"])) {
                    $fieldErrors["post-scheduled-at"] =
                        "日付を正しく設定してください。";
                }
            }

            // 入力値検証でエラーが存在すれば、そのエラー情報をレスポンスとして返す
            if (!empty($fieldErrors)) {
                return new JSONRenderer(["status" => "fieldErrors", "message" => $fieldErrors]);
            }

            // 画像を保存
            if ($postImageUploaded) {
                $imageHash = ImageHelper::savePostImage(
                    $_FILES["post-image"]["tmp_name"],
                    ImageHelper::imageTypeToExtension($_FILES["post-image"]["type"]),
                    $user->getUsername(),
                );
            }

            // 返信ポストかどうかを判定
            $isReply = intval($_POST["post-reply-to-id"] ?? "0") !== 0;
            if ($isReply) {
                $parentPost = $postDao->getPostById($_POST["post-reply-to-id"]);
                if ($parentPost === null) {
                    throw new Exception("返信先のポストが存在しません。");
                }
            }

            // 新しいPostオブジェクトを作成
            $status = "POSTED";

            if ($_POST["type"] === "schedule") {
                $status = "SCHEDULED";
            }

            $post = new Post(
                content: $_POST["post-content"],
                status: $status,
                user_id: $user->getUserId(),
                reply_to_id: $isReply ? $parentPost->getPostId() : null,
            );

            if ($postImageUploaded) {
                $post->setImageHash($imageHash);
            }

            if ($status === "SCHEDULED") {
                $post->setScheduledAt($_POST["post-scheduled-at"]);
            }

            // ポストを作成
            $success = $postDao->create($post);

            if (!$success) {
                throw new Exception("ポスト作成に失敗しました。");
            }

            // 通知を作成
            if ($isReply && $user->getUserId() !== $parentPost->getUserId()) {
                $notification = new Notification(
                    from_user_id: $user->getUserId(),
                    to_user_id: $parentPost->getUserId(),
                    source_id: $parentPost->getPostId(),
                    type: "REPLY",
                );
                $notificationDao = DAOFactory::getNotificationDAO();
                $result = $notificationDao->create($notification);
                if (!$result) {
                    throw new Exception("通知作成処理に失敗しました。");
                }
            }

            $message = $isReply ? "返信しました。" : "ポストを作成しました。";

            if ($status === "SCHEDULED") {
                $message = "ポストを予約しました。";
            }
            FlashData::setFlashData("success", $message);

            if ($isReply) {
                $redirectUrl = "/post?id=" . $_POST["post-reply-to-id"];
            }

            return new JSONRenderer(["status" => "success", "redirectUrl" => $redirectUrl]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポスト一覧取得
    "posts/init" => Route::create("posts/init", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postDao = DAOFactory::getPostDAO();
            $username = $_POST["user"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($username === "") {
                $user = Authenticate::getAuthenticatedUser();
            } else {
                $userDao = DAOFactory::getUserDAO();
                $user = $userDao->getByUsername($username);
            }

            $limit = $_POST["limit"] ?? 30;
            $offset = $_POST["offset"] ?? 0;
            $userId = $user->getUserId();
            $posts = $postDao->getUserPosts($userId, $authenticatedUser->getUserId(), $limit, $offset);

            for ($i = 0; $i < count($posts); $i++) {
                $posts[$i] = [
                    "postId" => $posts[$i]["post_id"],
                    "content" => $posts[$i]["content"],
                    "imagePath" => $posts[$i]["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "thumbnailPath" => $posts[$i]["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $posts[$i]["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($posts[$i]["updated_at"]),
                    "replyCount" => $posts[$i]["reply_count"],
                    "likeCount" => $posts[$i]["like_count"],
                    "liked" => $posts[$i]["liked"],
                    "name" => $posts[$i]["name"],
                    "username" => $posts[$i]["username"],
                    "profileImagePath" => $posts[$i]["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $posts[$i]["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $posts[$i]["username"],
                    "userType" => $posts[$i]["type"],
                    "deletable" => $authenticatedUser->getUsername() === $posts[$i]["username"],
                ];
            }
            return new JSONRenderer(["status" => "success", "posts" => $posts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // リプライ一覧取得
    "replies/init" => Route::create("replies/init", function(): HTTPRenderer {

        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postDao = DAOFactory::getPostDAO();
            $username = $_POST["user"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($username === "") {
                $user = Authenticate::getAuthenticatedUser();
            } else {
                $userDao = DAOFactory::getUserDAO();
                $user = $userDao->getByUsername($username);
            }

            $limit = $_POST["limit"] ?? 30;
            $offset = $_POST["offset"] ?? 0;
            $userId = $user->getUserId();
            $posts = $postDao->getUserReplies($userId, $authenticatedUser->getUserId(), $limit, $offset);

            for ($i = 0; $i < count($posts); $i++) {
                $posts[$i] = [
                    "postId" => $posts[$i]["post_id"],
                    "content" => $posts[$i]["content"],
                    "imagePath" => $posts[$i]["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "thumbnailPath" => $posts[$i]["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $posts[$i]["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($posts[$i]["updated_at"]),
                    "replyCount" => $posts[$i]["reply_count"],
                    "likeCount" => $posts[$i]["like_count"],
                    "liked" => $posts[$i]["liked"],
                    "name" => $posts[$i]["name"],
                    "username" => $posts[$i]["username"],
                    "profileImagePath" => $posts[$i]["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $posts[$i]["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $posts[$i]["username"],
                    "userType" => $posts[$i]["type"],
                    "deletable" => $authenticatedUser->getUsername() === $posts[$i]["username"],
                ];
            }
            return new JSONRenderer(["status" => "success", "posts" => $posts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // いいね一覧取得
    "likes/init" => Route::create("likes/init", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postDao = DAOFactory::getPostDAO();
            $username = $_POST["user"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            if ($username === "") {
                $user = Authenticate::getAuthenticatedUser();
            } else {
                $userDao = DAOFactory::getUserDAO();
                $user = $userDao->getByUsername($username);
            }

            $limit = $_POST["limit"] ?? 30;
            $offset = $_POST["offset"] ?? 0;
            $userId = $user->getUserId();
            $posts = $postDao->getUserLikes($userId, $authenticatedUser->getUserId(), $limit, $offset);

            for ($i = 0; $i < count($posts); $i++) {
                $posts[$i] = [
                    "postId" => $posts[$i]["post_id"],
                    "content" => $posts[$i]["content"],
                    "imagePath" => $posts[$i]["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "thumbnailPath" => $posts[$i]["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $posts[$i]["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($posts[$i]["updated_at"]),
                    "replyCount" => $posts[$i]["reply_count"],
                    "likeCount" => $posts[$i]["like_count"],
                    "liked" => $posts[$i]["liked"],
                    "name" => $posts[$i]["name"],
                    "username" => $posts[$i]["username"],
                    "profileImagePath" => $posts[$i]["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $posts[$i]["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $posts[$i]["username"],
                    "userType" => $posts[$i]["type"],
                    "deletable" => $authenticatedUser->getUsername() === $posts[$i]["username"],
                ];
            }

            return new JSONRenderer(["status" => "success", "posts" => $posts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポスト詳細
    "post" => Route::create("post", function(): HTTPRenderer {
        return new HTMLRenderer("pages/post_detail", []);
    })->setMiddleware(["auth", "verify"]),
    // ポスト詳細取得
    "post/detail" => Route::create("post/detail", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postId = $_POST["postId"];

            if ($postId === null) {
                throw new Exception("リクエストデータが不適切です。");
            }

            $authenticatedUser = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();
            $post = $postDao->getPost($postId, $authenticatedUser->getUserId());

            if ($post === null) {
                $detailPost = null;
            } else {
                $detailPost = [
                    "postId" => $post["post_id"],
                    "content" => $post["content"],
                    "imagePath" => $post["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "thumbnailPath" => $post["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $post["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($post["updated_at"]),
                    "replyCount" => $post["reply_count"],
                    "likeCount" => $post["like_count"],
                    "liked" => $post["liked"],
                    "name" => $post["name"],
                    "username" => $post["username"],
                    "profileImagePath" => $post["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $post["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $post["username"],
                    "userType" => $post["type"],
                    "deletable" => $authenticatedUser->getUsername() === $post["username"],
                ];

                if ($post["reply_to_id"]) {
                    $post = $postDao->getPost($post["reply_to_id"], $authenticatedUser->getUserId());

                    $parentPost = [
                        "postId" => $post["post_id"],
                        "content" => $post["content"],
                        "imagePath" => $post["image_hash"] ?
                            POST_ORIGINAL_IMAGE_FILE_DIR . $post["image_hash"] :
                            "",
                        "thumbnailPath" => $post["image_hash"] ?
                            POST_THUMBNAIL_IMAGE_FILE_DIR . $post["image_hash"] :
                            "",
                        "postPath" => "/post?id=" . $post["post_id"],
                        "postedAt" => DateTimeHelper::getTimeDiff($post["updated_at"]),
                        "replyCount" => $post["reply_count"],
                        "likeCount" => $post["like_count"],
                        "liked" => $post["liked"],
                        "name" => $post["name"],
                        "username" => $post["username"],
                        "profileImagePath" => $post["profile_image_hash"] ?
                            PROFILE_IMAGE_FILE_DIR . $post["profile_image_hash"] :
                            PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                        "profilePath" => "/profile?user=" . $post["username"],
                        "userType" => $post["type"],
                        "deletable" => $authenticatedUser->getUsername() === $post["username"],
                    ];
                }
            }

            return new JSONRenderer([
                "status" => "success",
                "post" => $detailPost,
                "parentPost" => $parentPost ?? null,
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポストリプライ
    "post/replies" => Route::create("/api/post/replies", function(): HTTPRenderer {

        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postId = $_POST["postId"];
            $replyLimit = $_POST["replyLimit"];
            $replyOffset = $_POST["replyOffset"];

            if ($postId === null) {
                throw new Exception("リクエストデータが不適切です。");
            }

            $authenticatedUser = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();
            $replies = $postDao->getReplies($postId, $authenticatedUser->getUserId(), $replyLimit, $replyOffset);

            $postReplies = array_map(function($post) use ($authenticatedUser) {
                return [
                    "postId" => $post["post_id"],
                    "content" => $post["content"],
                    "imagePath" => $post["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "thumbnailPath" => $post["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $post["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($post["updated_at"]),
                    "replyCount" => $post["reply_count"],
                    "likeCount" => $post["like_count"],
                    "liked" => $post["liked"],
                    "name" => $post["name"],
                    "username" => $post["username"],
                    "profileImagePath" => $post["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $post["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $post["username"],
                    "userType" => $post["type"],
                    "deletable" => $authenticatedUser->getUsername() === $post["username"],
                ];
            }, $replies);

            return new JSONRenderer(["status" => "success", "replies" => $postReplies,]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポストいいね
    "post/like" => Route::create("post/like", function(): HTTPRenderer {
        $resBody = ["success" => true];

        try {
            $likeDao = DAOFactory::getLikeDAO();
            $postDao = DAOFactory::getPostDAO();

            $postId = $_POST["post_id"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            $exists = $likeDao->exists($authenticatedUser->getUserId(), $postId);
            if ($exists) throw new Exception("既にいいねしています。");

            $post = $postDao->getPostById($postId);
            if ($post === null) throw new Exception("いいねするポストが存在しません。");

            $like = new Like(
                user_id: $authenticatedUser->getUserId(),
                post_id: $post->getPostId(),
            );
            $likeDao->create($like);

            if ($authenticatedUser->getUserId() !== $post->getUserId()) {
                $notification = new Notification(
                    from_user_id: $authenticatedUser->getUserId(),
                    to_user_id: $post->getUserId(),
                    source_id: $post->getPostId(),
                    type: "LIKE",
                );
                $notificationDao = DAOFactory::getNotificationDAO();
                $result = $notificationDao->create($notification);
                if (!$result) {
                    throw new Exception("通知作成処理に失敗しました。");
                }
            }

            return new JSONRenderer($resBody);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resBody["success"] = false;
            $resBody["error"] = "エラーが発生しました。";
            return new JSONRenderer($resBody);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポストいいね解除
    "post/unlike" => Route::create("post/unlike", function(): HTTPRenderer {
        $resBody = ["success" => true];

        try {
            $likeDao = DAOFactory::getLikeDAO();
            $postId = $_POST["post_id"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            $exists = $likeDao->exists($authenticatedUser->getUserId(), $postId);
            if (!$exists) throw new Exception("既にいいねされていません。");

            $likeDao->delete($authenticatedUser->getUserId(), $postId);

            return new JSONRenderer($resBody);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resBody["success"] = false;
            $resBody["error"] = "エラーが発生しました。";
            return new JSONRenderer($resBody);
        }
    })->setMiddleware(["auth", "verify"]),
    // 予約ポスト一覧取得
    "post/scheduled_posts/init" => Route::create("/api/post/scheduled_posts", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $limit = $_POST["limit"];
            $offset = $_POST["offset"];

            $authenticatedUser = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();
            $scheduledPosts = $postDao->getScheduledPosts($authenticatedUser->getUserId(), $limit, $offset);

            $reservationPosts = array_map(function($post) {
                return [
                    "postId" => $post["post_id"],
                    "content" => $post["content"],
                    "imagePath" => $post["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "thumbnailPath" => $post["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $post["image_hash"] :
                        "",
                    "scheduledAt" => DateTimeHelper::formatJpDateTime(DateTimeHelper::stringToDatetime($post["scheduled_at"])),
                ];
            }, $scheduledPosts);

            return new JSONRenderer(["status" => "success", "post" => $reservationPosts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // ポスト削除
    "post/delete" => Route::create("post/delete", function(): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $postDao = DAOFactory::getPostDAO();
            $postId = $_POST["post_id"];
            $authenticatedUser = Authenticate::getAuthenticatedUser();

            $post = $postDao->getPost($postId, $authenticatedUser->getUserId());
            if ($post["username"] !== $authenticatedUser->getUsername()) throw new Exception("このポストは削除できません。");

            $postDao->delete($postId);
            if ($post["image_hash"]) {
                ImageHelper::deletePostImage($post["image_hash"]);
            }

            return new JSONRenderer(["status" => "success", "message" => "ポストを削除しました。"]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // タイムライン
    "timeline" => Route::create("timeline", function (): HTTPRenderer {
        return new HTMLRenderer("pages/timeline", []);
    })->setMiddleware(["auth", "verify"]),
    // タイムライントレンド取得
    "timeline/trend/init" => Route::create("timeline/trend/init", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $authenticatedUser = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();

            $limit = $_POST["limit"] ?? 30;
            $offset = $_POST["offset"] ?? 0;
            $userId = $authenticatedUser->getUserId();
            $posts = $postDao->getTrendTimelinePosts($userId, $limit, $offset);

            for ($i = 0; $i < count($posts); $i++) {
                $posts[$i] = [
                    "postId" => $posts[$i]["post_id"],
                    "content" => $posts[$i]["content"],
                    "imagePath" => $posts[$i]["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "thumbnailPath" => $posts[$i]["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $posts[$i]["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($posts[$i]["updated_at"]),
                    "replyCount" => $posts[$i]["reply_count"],
                    "likeCount" => $posts[$i]["like_count"],
                    "liked" => $posts[$i]["liked"],
                    "name" => $posts[$i]["name"],
                    "username" => $posts[$i]["username"],
                    "profileImagePath" => $posts[$i]["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $posts[$i]["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $posts[$i]["username"],
                    "userType" => $posts[$i]["type"],
                    "deletable" => $authenticatedUser->getUsername() === $posts[$i]["username"],
                ];
            }

            return new JSONRenderer(["status" => "success", "posts" => $posts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // タイムラインフォロー取得
    "timeline/follow/init" => Route::create("timeline/follow/init", function (): HTTPRenderer {
        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                throw new Exception("Invalid request method");
            }

            $authenticatedUser = Authenticate::getAuthenticatedUser();
            $postDao = DAOFactory::getPostDAO();

            $limit = $_POST["limit"] ?? 30;
            $offset = $_POST["offset"] ?? 0;
            $userId = $authenticatedUser->getUserId();
            $posts = $postDao->getFollowTimelinePosts($userId, $limit, $offset);

            for ($i = 0; $i < count($posts); $i++) {
                $posts[$i] = [
                    "postId" => $posts[$i]["post_id"],
                    "content" => $posts[$i]["content"],
                    "imagePath" => $posts[$i]["image_hash"] ?
                        POST_ORIGINAL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "thumbnailPath" => $posts[$i]["image_hash"] ?
                        POST_THUMBNAIL_IMAGE_FILE_DIR . $posts[$i]["image_hash"] :
                        "",
                    "postPath" => "/post?id=" . $posts[$i]["post_id"],
                    "postedAt" => DateTimeHelper::getTimeDiff($posts[$i]["updated_at"]),
                    "replyCount" => $posts[$i]["reply_count"],
                    "likeCount" => $posts[$i]["like_count"],
                    "liked" => $posts[$i]["liked"],
                    "name" => $posts[$i]["name"],
                    "username" => $posts[$i]["username"],
                    "profileImagePath" => $posts[$i]["profile_image_hash"] ?
                        PROFILE_IMAGE_FILE_DIR . $posts[$i]["profile_image_hash"] :
                        PROFILE_IMAGE_FILE_DIR . "default_profile_image.png",
                    "profilePath" => "/profile?user=" . $posts[$i]["username"],
                    "userType" => $posts[$i]["type"],
                    "deletable" => $authenticatedUser->getUsername() === $posts[$i]["username"],
                ];
            }

            return new JSONRenderer(["status" => "success", "posts" => $posts]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(["status" => "error", "message" => "エラーが発生しました。"]);
        }
    })->setMiddleware(["auth", "verify"]),
    // 通知ページ
    "notifications" => Route::create("notifications", function(): HTTPRenderer {
        return new HTMLRenderer("pages/notifications", []);
    })->setMiddleware(["auth", "verify"]),
];
