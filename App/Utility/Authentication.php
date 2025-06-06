<?php

declare(strict_types=1);

namespace App\Utility;

use App\Config\Config;
use App\Database\DBConnection;
use App\Utility\Security;
use App\Utility\UrlPath;
use App\Utility\Info;
use App\Utility\Response;
use App\Core\MTTSessionHandler;

class Authentication extends Security
{
    /* ===================================================================================================================== */

    public static function checkReadAccess(?int $listId = null)
    {
        self::checkToken();
        if (self::isLogged()) {
            return true;
        }

        $db = DBConnection::instance();
        if ($listId !== null) {
            $id = $db->sq("SELECT id FROM {$db->prefix}lists WHERE id=? AND published=1", array($listId));
            if ($id) {
                return;
            }
        }

        Response::jsonExit(array('total' => 0, 'list' => array(), 'denied' => 1));
    }

    public static function checkWriteAccess(?int $listId = null)
    {
        self::checkToken();
        if (self::haveWriteAccess($listId)) {
            return;
        }

        http_response_code(403);

        Response::jsonExit(array('total' => 0, 'list' => array(), 'denied' => 1));
    }

    public static function haveWriteAccess(?int $listId = null): bool
    {
        if (self::isReadonly()) {
            return false;
        }
        // check list exist
        if ($listId !== null && $listId != -1) {
            $db = DBConnection::instance();
            $count = $db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=?", array($listId));

            if (!$count) {
                return false;
            }
        }
        return true;
    }

    /* ===================================================================================================================== */

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
        return Security::isValidSignature($_SESSION['sign'], session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
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
            $_SESSION['sign'] = Security::idSignature(session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
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
        $token = Security::generateUUID();
        if (self::needAuth()) {
            $_SESSION['token'] = $token;
        } else {
            if (PHP_VERSION_ID < 70300) {
                setcookie('mtt-token', $token, 0, UrlPath::urlDir(Info::getUnsafeMttinfo('mtt_url')) . '; samesite=lax', '', false, true);
            } else {
                setcookie('mtt-token', $token, [
                    'path' => UrlPath::urlDir(Info::getUnsafeMttinfo('mtt_url')),
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
        $path = UrlPath::urlDir(Config::get('url') == '' ? UrlPath::getRequestUri() : Config::getUrl('url'));

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
}
