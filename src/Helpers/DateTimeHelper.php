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

    public static function getTimeDiff(string $dateTimeString): string {
        // 現在時刻データ
        $now = new DateTime("now", self::getTimezone());

        // 比較対象の日時データ
        $dateTime = new DateTime($dateTimeString, self::getTimezone());

        // 経過時間を秒単位で取得
        $seconds = $now->getTimestamp() - $dateTime->getTimestamp();

        if ($seconds < 60) {
            // 1分未満
            return $seconds . "秒前";
        } elseif ($seconds < 3600) {
            // 1時間未満
            $minutes = floor($seconds / 60);
            return $minutes . "分前";
        } elseif ($seconds < 86400) {
            // 1日未満
            $hours = floor($seconds / 3600);
            return $hours . "時間前";
        } elseif ($seconds < 86400 * 7) {
            // 1週間未満
            $days = floor($seconds / 86400);
            return $days . "日前";
        } else {
            // 1週間以上の場合は日付を表示
            $format = $dateTime->format("Y") === $now->format("Y") ? "n月j日" : "Y年n月j日";
            return $dateTime->format($format);
        }
    }

    public static function getCurrentDateTime(): DateTime {
        return new DateTime("now", self::getTimeZone());
    }

    public static function formatDateTime(DateTime $dateTime): string {
        return $dateTime->setTimezone(self::getTimeZone())->format("Y-m-d H:i:s");
    }

}