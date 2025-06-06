<?php

declare(strict_types=1);

namespace App\Utility;

use App\Config\Config;
use App\Lang\Lang;

/**
 * Formatter utility class to handle date formatting and translations.
 */

class Formatter
{
    public static function timestampToDatetime($timestamp, $forceTime = false): string
    {
        $format = Config::get('dateformat');
        if ($forceTime || Config::get('showtime')) {
            $format .= ' ' . (Config::get('clock') == 12 ? 'g:i A' : 'H:i');
        }
        return self::formatTime($format, $timestamp);
    }

    public static function formatTime($format, $timestamp = 0): string
    {
        $lang = Lang::instance();
        if ($timestamp == 0) {
            $timestamp = time();
        }
        $newformat = strtr($format, array('F' => '%1', 'M' => '%2'));
        $adate = explode(',', date('n,' . $newformat, $timestamp), 2);
        $s = $adate[1];

        if ($newformat != $format) {
            $am = (int)$adate[0];
            $ml = $lang->get('months_long');
            $ms = $lang->get('months_short');
            $F = $ml[$am - 1];
            $M = $ms[$am - 1];
            $s = strtr($s, array('%1' => $F, '%2' => $M));
        }
        return $s;
    }

    //phpcs:ignore
    public static function _e(string $s)
    {
        echo self::__($s, true);
    }

    public static function __(string $s, bool $escape = false, ?string $arg = null)
    {
        $v = Lang::instance()->get($s);
        if (null !== $arg) {
            $v = sprintf($v, $arg);
        }
        return $escape ? htmlspecialchars($v) : $v;
    }

    /* ===================================================================================================================== */

    public static function formatDate3($format, $ay, $am, $ad, $lang)
    {
        # F - month long, M - month short
        # m - month 2-digit, n - month 1-digit
        # d - day 2-digit, j - day 1-digit
        $ml = $lang->get('months_long');
        $ms = $lang->get('months_short');
        $Y = $ay;
        $YC = 100 * floor($Y / 100); //...1900,2000,2100...
        if ($YC == 2000) {
            $y = $Y < $YC + 10 ? '0' . ($Y - $YC) : $Y - $YC;
        } else {
            $y = $Y;
        }
        $n = $am;
        $m = $n < 10 ? '0' . $n : $n;
        $F = $ml[$am - 1];
        $M = $ms[$am - 1];
        $j = $ad;
        $d = $j < 10 ? '0' . $j : $j;
        return strtr($format, array('Y' => $Y, 'y' => $y, 'F' => $F, 'M' => $M, 'n' => $n, 'm' => $m, 'd' => $d, 'j' => $j));
    }

    public static function daysInMonth(int $m, int $y = 0): int
    {
        if ($y == 0) {
            $y = (int)date('Y');
        }
        $isLeap = (0 == $y % 4) && ((0 != $y % 100) || (0 == $y % 400));
        $a = array(1 => 31, ($isLeap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        if (isset($a[$m])) {
            return $a[$m];
        } else {
            return 0;
        }
    }
}
