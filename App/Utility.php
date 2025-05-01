<?php

declare(strict_types=1);

namespace App;

use App\Config\Config;
use App\Utility2;
use App\Core\MTTSessionHandler;
use App\Lang\Lang;
use App\Core\MTTNotificationCenter;
use App\Core\MTTExtensionLoader;
use Exception;

class Utility
{
    public static function needAuth(): bool
    {
        return (Config::get('password') != '') ? true : false;
    }

    public static function isLogged(): bool
    {
        if (!self::needAuth()) {
            return true;
        }
        if (!isset($_SESSION['logged']) || !isset($_SESSION['sign'])) {
            return false;
        }
        if (!(int)$_SESSION['logged']) {
            return false;
        }
        return Utility2::isValidSignature($_SESSION['sign'], session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
    }

    public static function isReadonly(): bool
    {
        if (!self::isLogged()) {
            return true;
        }
        return false;
    }

    public static function updateSessionLogged(bool $logged)
    {
        if ($logged) {
            $_SESSION['logged'] = 1;
            $_SESSION['sign'] = Utility2::idSignature(session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
        } else {
            unset($_SESSION['logged']);
            unset($_SESSION['sign']);
        }
    }

    public static function accessToken(): string
    {
        if (self::needAuth()) {
            if (!isset($_SESSION)) {
                return '';
            }
            return $_SESSION['token'] ?? '';
        } else {
            if (!isset($_COOKIE)) {
                return '';
            }
            return $_COOKIE['mtt-token'] ?? '';
        }
    }

    /**
    * Check if HTTP request have required MTT-Token header with value
    * the same as stored in session (if password set) or mtt-token cookie (if no password).
    * Prohibits further execution if no tokens are found.
    * @return void
    */
    public static function checkToken()
    {
        $token = self::accessToken();

        //  || !isset($_SERVER['HTTP_MTT_TOKEN']) || $_SERVER['HTTP_MTT_TOKEN'] != $token
        if ($token == '') {
            http_response_code(403);
            die("Access denied! No token provided.");
        }
    }

    public static function updateToken(): string
    {
        $token = Utility2::generateUUID();
        if (self::needAuth()) {
            $_SESSION['token'] = $token;
        } else {
            if (PHP_VERSION_ID < 70300) {
                setcookie('mtt-token', $token, 0, Utility2::urlDir(self::getUnsafeMttinfo('mtt_url')) . '; samesite=lax', '', false, true);
            } else {
                setcookie('mtt-token', $token, [
                    'path' => Utility2::urlDir(self::getUnsafeMttinfo('mtt_url')),
                    'httponly' => true,
                    'samesite' => 'lax'
                ]);
            }
            $_COOKIE['mtt-token'] = $token;
        }
        return $token;
    }

    public static function setupAndStartSession()
    {
        session_set_save_handler(new MTTSessionHandler());

        ini_set('session.use_cookies', true);
        ini_set('session.use_only_cookies', true);

        /*
            After any request we may have 14 days of inactivity (i.e. not requesting session data),
            then we have to re-login (look at MTTSessionHandler).
            Activity without re-login lasts for max 60 days, the cookie lifetime, then cookie dies
            and we have to re-login having new session id.
        */

        $lifetime = 5184000; # 60 days session cookie lifetime
        $path = Utility2::urlDir(Config::get('url') == '' ? Utility2::getRequestUri() : Config::getUrl('url'));

        if (PHP_VERSION_ID < 70300) {
            # this is a known samesite flag workaround, was fixed in 7.3
            session_set_cookie_params($lifetime, $path . '; samesite=lax', null, null, true);
        } else {
            /** @disregard P1006 available in php 7.3 */
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $path,
                'httponly' => true,
                'samesite' => 'lax'
            ]);
        }
        session_name('mtt-session');
        session_start();
    }

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

    public static function mttinfo($v)
    {
        echo self::getMttinfo($v);
    }

    public static function getMttinfo($v)
    {
        return htmlspecialchars(self::getUnsafeMttinfo($v));
    }

    /*
    * Returned values from get_unsafe_mttinfo() can be unsafe for html.
    * But '\r' and '\n' in URLs taken from config are removed.
    */
    public static function getUnsafeMttinfo($v)
    {
        global $_mttinfo;
        if (isset($_mttinfo[$v])) {
            return $_mttinfo[$v];
        }
        switch ($v) {
            case 'theme_url':
                $_mttinfo['theme_url'] = ''; // self::getUnsafeMttinfo('mtt_uri');
                return $_mttinfo['theme_url'];
            case 'content_url':
                $_mttinfo['content_url'] = ''; // self::getUnsafeMttinfo('mtt_uri');
                return $_mttinfo['content_url'];
            case 'url':
                /* full url to homepage: directory with root index.php or custom index file in the root. */
                /* ex: http://my.site/mytinytodo/   or  https://my.site/mytinytodo/home_for_2nd_theme.php  */
                /* Should not contain a query string. Have to be set in config if custom port is used or wrong detection. */
                $_mttinfo['url'] = Config::getUrl('url');
                if ($_mttinfo['url'] == '') {
                    $is_https = self::isHttps();
                    $_mttinfo['url'] = ($is_https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . Utility2::urlDir(Utility2::getRequestUri());
                }
                return $_mttinfo['url'];
            case 'mtt_url':
                /* Directory with settings.php. No need to set if you use default directory structure. */
                $_mttinfo['mtt_url'] = Config::getUrl('mtt_url'); // need to have a trailing slash
                if ($_mttinfo['mtt_url'] == '') {
                    $_mttinfo['mtt_url'] = Utility2::urlDir(self::getUnsafeMttinfo('url'), 0);
                }
                return $_mttinfo['mtt_url'];
            case 'mtt_uri':
                $_mttinfo['mtt_uri'] = Config::getUrl('mtt_url'); // need to have a trailing slash
                if ($_mttinfo['mtt_uri'] == '') {
                    if (''  !=  $url = Config::getUrl('url')) {
                        $_mttinfo['mtt_uri'] = Utility2::urlDir($url);
                    } else {
                        $_mttinfo['mtt_uri'] = Utility2::urlDir(Utility2::getRequestUri());
                    }
                }
                return $_mttinfo['mtt_uri'];
            case 'api_url':
                /* URL for API, like http://localhost/mytinytodo/api/. No need to set by default. */
                $_mttinfo['api_url'] = Config::getUrl('api_url'); // need to have a trailing slash
                if ($_mttinfo['api_url'] == '') {
                    if (defined('MTT_API_USE_PATH_INFO')) {
                        // $_mttinfo['api_url'] = self::getUnsafeMttinfo('mtt_uri') . 'api/';
                        $_mttinfo['api_url'] = '/mytinytodo/api/';
                    } else {
                        // $_mttinfo['api_url'] = self::getUnsafeMttinfo('mtt_uri') . 'api?_path=/';
                        $_mttinfo['api_url'] = '/mytinytodo/api?_path=/';
                    }
                }

                return $_mttinfo['api_url'];
            case 'title':
                $_mttinfo['title'] = (Config::get('title') != '') ? Config::get('title') : self::__('My Tiny Todolist');
                return $_mttinfo['title'];
            case 'version':
                $_mttinfo['version'] = Version::VERSION;
                return $_mttinfo['version'];
            case 'appearance':
                $_mttinfo['appearance'] = Config::get('appearance');
                return $_mttinfo['appearance'];
        }
    }

    public static function resetMttinfo($key)
    {
        global $_mttinfo;
        unset($_mttinfo[$key]);
    }

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

    public static function jsonExit($data)
    {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        MTTNotificationCenter::postDidFinishRequestNotification();
        exit;
    }

    public static function logAndDie($userText, $errText = null)
    {
        $errText === null ? error_log($userText) : error_log($errText);
        if (ini_get('display_errors')) {
            echo htmlspecialchars($userText);
        } else {
            echo "Error! See details in error log.";
        }
        exit(1);
    }

    public static function loadExtensions()
    {
        $a = Config::get('extensions');
        if (!$a || !is_array($a)) {
            return;
        }
        foreach ($a as $ext) {
            if (is_string($ext)) {
                try {
                    MTTExtensionLoader::loadExtension($ext);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }

    public static function filever(string $dir, string $filename)
    {
        if (!MTT_DEBUG) {
            return self::mttinfo('version');
        }
        $prefix = self::getMttinfo('version') . '-' . time();
        $path = null;
        if ($dir == 'content') {
            $path = MTTPATH . 'content/';
        } else if ($dir == 'theme') {
            $path = MTTPATH . '/';
        } else {
            return print($prefix . '-unknown');
        }
        $fullPath = $path . $filename;
        if (!file_exists($fullPath)) {
            return print($prefix . '-not-found');
        }
        $mtime = filemtime($fullPath);
        if ($mtime === false) {
            return print($prefix . '-no-access');
        }
        return print($mtime);
    }
}
