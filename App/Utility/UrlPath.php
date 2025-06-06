<?php

declare(strict_types=1);

namespace App\Utility;

class UrlPath
{
    public static function getRequestUri()
    {
        // Do not use HTTP_X_REWRITE_URL due to CVE-2018-14773
        // SCRIPT_NAME or PATH_INFO ?
        if (isset($_SERVER['SCRIPT_NAME'])) {
            return $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {  // IIS 5.0 CGI
            $uri = $_SERVER['ORIG_PATH_INFO']; //has no query
            if (!empty($_SERVER['QUERY_STRING'])) {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
            return $uri;
        }
    }

    public static function urlDir($url, $onlyPath = 1)
    {
        if (false !== $p = strpos($url, '?')) {
            $url = substr($url, 0, $p); # to avoid parse errors on strange query strings
        }
        if ($onlyPath) {
            $url = parse_url($url, PHP_URL_PATH);
        }
        if ($url == '') {
            return '/';
        }
        if (substr($url, -1) == '/') {
            return $url;
        }
        if (false !== $p = strrpos($url, '/')) {
            return substr($url, 0, $p + 1);
        }
        return '/';
    }

    public static function removeNewLines($s)
    {
        return str_replace(["\r","\n"], '', $s);
    }
}
