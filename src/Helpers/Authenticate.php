<?php

namespace Helpers;

use Database\DataAccess\DAOFactory;
use Exception;
use Exceptions\AuthenticationFailureException;
use Models\User;

class Authenticate {

    // 認証されたユーザーの状態をこのクラス変数に保持する
    private static ?User $authenticatedUser = null;
    private const USER_ID_SESSION_WKY = "user_id";
    
    public static function loginAsUser(User $user): bool {
        if ($user->getUserId() === null) {
            throw new \Exception("Cannot login a user with no ID.");
        }

        if (isset($_SESSION[self::USER_ID_SESSION_WKY])) {
            throw new \Exception("User is already logged in. Logout before continuing");
        }

        $_SESSION[self::USER_ID_SESSION_WKY] = $user->getUserId();
        return true;
    }

    public static function logoutUser(): bool {
        if (isset($_SESSION[self::USER_ID_SESSION_WKY])) {
            unset($_SESSION[self::USER_ID_SESSION_WKY]);
            self::$authenticatedUser = null;
            return true;
        } else {
            throw new \Exception("No user to logout");
        }
    }

    public static function isLoggedin(): bool {
        self::retrieveAuthenticatedUser();
        return self::$authenticatedUser !== null;
    }

    public static function getAuthenticatedUser(): ?User {
        self::retrieveAuthenticatedUser();
        return self::$authenticatedUser;
    }

    public static function authenticate(string $email, string $password): User {
        $userDAO = DAOFactory::getUserDAO();
        self::$authenticatedUser = $userDAO->getByEmail($email);

        if (self::$authenticatedUser === null) {
            throw new Exception("Could not retrieve user by specified email %s " . $email);
        }

        $hashedPassword = $userDAO->getHashedPasswordById(self::$authenticatedUser->getUserId());

        if (password_verify($password, $hashedPassword)) {
            self::loginAsUser(self::$authenticatedUser);
            return self::$authenticatedUser;
        } else {
            throw new AuthenticationFailureException("Invalid password.");
        }
        
    }

    private static function retrieveAuthenticatedUser(): void {
        if (!isset($_SESSION[self::USER_ID_SESSION_WKY])) {
            return;
        }
        $userDAO = DAOFactory::getUserDAO();
        self::$authenticatedUser = $userDAO->getById($_SESSION[self::USER_ID_SESSION_WKY]);
    }

}
