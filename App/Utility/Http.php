<?php

declare(strict_types=1);

namespace App\Utility;

class Http
{
    public static function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
            return true;
        }
        if (defined('MTT_USE_HTTPS')) {
            return true;
        }
        // This HTTP header can be overridden by user agent!
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            return true;
        }
        return false;
    }

    public static function setNocacheHeaders()
    {
        // little more info at https://www.php.net/manual/en/function.session-cache-limiter.php
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Expires: Wed, 29 Apr 2009 10:00:00 GMT');
        header('Pragma: no-cache'); // for old HTTP/1.0 intermediate caches
    }

    public static function getURIPrefix(): string
    {
        // Prefer environment variable, fallback to server software detection
        $env = getenv('MTT_ENV');

        if ($env === 'development') {
            return '/mytinytodo';
        }

        if ($env === 'production') {
            return '';
        }

        // Fallback for legacy detection
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
            return '/mytinytodo';
        }

        return '';
    }
}
