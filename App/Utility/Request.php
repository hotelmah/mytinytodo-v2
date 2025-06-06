<?php

declare(strict_types=1);

namespace App\Utility;

/**
 * Request utility class to handle GET, POST, and SERVER requests.
 */
class Request
{
    public static function get($param, $defvalue = '')
    {
        if (!isset($_GET[$param])) {
            return $defvalue;
        } else {
            return $_GET[$param];
        }
    }

    public static function post($param, $defvalue = '')
    {
        if (!isset($_POST[$param])) {
            return $defvalue;
        } else {
            return $_POST[$param];
        }
    }

    public static function server($param, $defvalue = '')
    {
        if (!isset($_SERVER[$param])) {
            return $defvalue;
        } else {
            return $_SERVER[$param];
        }
    }
}
