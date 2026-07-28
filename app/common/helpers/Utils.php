<?php

namespace common\helpers;

use yii\base\Security;

class Utils
{
    public const FRIENDLY_DT_FORMAT = 'l F j, Y g:iA T';
    public const DT_ISO8601 = 'Y-m-d\TH:i:s\Z';

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 32)
    {
        $security = new Security();
        return $security->generateRandomString($length);
    }

    /**
     * Return a random string of numbers
     * @param int $length [default=4]
     * @return string
     */
    public static function getRandomDigits($length = 4)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= random_int(0, 9);
        }
        return $result;
    }

    /**
     * Return human readable date time
     * @param int|string|null $timestamp Either a unix timestamp or a date in string format
     * @return string
     * @throws \Exception
     */
    public static function getFriendlyDate($timestamp = null)
    {
        $timestamp ??= time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1468865838);
        }
        return date(self::FRIENDLY_DT_FORMAT, $timestamp);
    }

    /**
     * @param integer|string|null $timestamp time as unix timestamp, MYSQL datetime. If omitted,
     *        the current time is used.
     * @return string date in ISO8601 format (e.g. 2019-01-08T12:54:00Z)
     * @throws \Exception if a badly-formatted time string is provided in $timestamp
     */
    public static function getIso8601($timestamp = null)
    {
        $timestamp ??= time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1546977533);
        }
        $dt = date_create_from_format('U', $timestamp);
        return $dt->format(self::DT_ISO8601);
    }
}
