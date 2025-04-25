<?php

namespace Helpers;

use DateTime;
use DateTimeZone;

class DateTimeHelper {

    private static ?DateTimeZone $timezone = null;

    private static function getTimeZone(): DateTimeZone {
        if (self::$timezone === null) {
            self::$timezone = new DateTimeZone("Asia/Tokyo");
        }
        
        return self::$timezone;
    }

    public static function getCurrentDateTime(): DateTime {
        return new DateTime("now", self::getTimeZone());
    }

    public static function formatDateTime(DateTime $dateTime): string {
        return $dateTime->setTimezone(self::getTimeZone())->format("Y-m-d H:i:s");
    }

}