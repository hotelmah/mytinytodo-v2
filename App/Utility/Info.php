<?php

declare(strict_types=1);

namespace App\Utility;

use App\Utility\UrlPath;
use App\Config\Config;
use App\Utility\Http;
use App\Utility\Formatter;
use App\Version;

class Info extends UrlPath
{
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
                    $is_https = Http::isHttps();
                    $_mttinfo['url'] = ($is_https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . self::urlDir(self::getRequestUri());
                }
                return $_mttinfo['url'];
            case 'mtt_url':
                /* Directory with settings.php. No need to set if you use default directory structure. */
                $_mttinfo['mtt_url'] = Config::getUrl('mtt_url'); // need to have a trailing slash
                if ($_mttinfo['mtt_url'] == '') {
                    $_mttinfo['mtt_url'] = self::urlDir(self::getUnsafeMttinfo('url'), 0);
                }
                return $_mttinfo['mtt_url'];
            case 'mtt_uri':
                $_mttinfo['mtt_uri'] = Config::getUrl('mtt_url'); // need to have a trailing slash
                if ($_mttinfo['mtt_uri'] == '') {
                    if (''  !=  $url = Config::getUrl('url')) {
                        $_mttinfo['mtt_uri'] = self::urlDir($url);
                    } else {
                        $_mttinfo['mtt_uri'] = self::urlDir(self::getRequestUri());
                    }
                }
                return $_mttinfo['mtt_uri'];
            case 'api_url':
                /* URL for API, like http://localhost/mytinytodo/api/. No need to set by default. */
                $_mttinfo['api_url'] = Config::getUrl('api_url'); // need to have a trailing slash
                if ($_mttinfo['api_url'] == '') {
                    if (true) {
                        // $_mttinfo['api_url'] = self::getUnsafeMttinfo('mtt_uri') . 'api/';
                        $_mttinfo['api_url'] = '/mytinytodo/api/';
                    } else {
                        // $_mttinfo['api_url'] = self::getUnsafeMttinfo('mtt_uri') . 'api?_path=/';
                        $_mttinfo['api_url'] = '/mytinytodo/api?_path=/';
                    }
                }

                return $_mttinfo['api_url'];
            case 'title':
                $_mttinfo['title'] = (Config::get('title') != '') ? Config::get('title') : Formatter::__('My Tiny Todolist');
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
}
