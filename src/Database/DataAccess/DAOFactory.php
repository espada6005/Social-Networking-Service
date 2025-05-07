<?php

namespace Database\DataAccess;

use Database\DataAccess\Implementations\FollowDAOImpl;
use Database\DataAccess\Implementations\LikeDAOImpl;
use Database\DataAccess\Implementations\MessageDAOImpl;
use Database\DataAccess\Implementations\NotificationDAOImpl;
use Database\DataAccess\Implementations\PostDAOImpl;
use Database\DataAccess\Implementations\UserDAOImpl;
use Database\DataAccess\Interfaces\FollowDAO;
use Database\DataAccess\Interfaces\LikeDAO;
use Database\DataAccess\Interfaces\MessageDAO;
use Database\DataAccess\Interfaces\NotificationDAO;
use Database\DataAccess\Interfaces\PostDAO;
use Database\DataAccess\Interfaces\UserDAO;
use Helpers\Settings;

class DAOFactory {

    public static function getUserDAO(): UserDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new UserDAOImpl(),
            default => new UserDAOImpl(),
        };
    }

    public static function getFollowDAO(): FollowDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new FollowDAOImpl(),
            default => new FollowDAOImpl(),
        };
    }

    public static function getPostDAO(): PostDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new PostDAOImpl(),
            default => new PostDAOImpl(),
        };
    }

    public static function getLikeDAO(): LikeDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new LikeDAOImpl(),
            default => new LikeDAOImpl(),
        };
    }

    public static function getNotificationDAO(): NotificationDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new NotificationDAOImpl(),
            default => new NotificationDAOImpl(),
        };
    }

    public static function getMessageDAO(): MessageDAO {
        $driver = Settings::env("DATABASE_DRIVER");

        return match ($driver) {
            "mysql" => new MessageDAOImpl(),
            default => new MessageDAOImpl(),
        };
    }

}
