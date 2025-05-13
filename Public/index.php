<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once('../vendor/autoload.php');

use App\Route;
use App\Config\Config;
use App\Utility;
use App\Lang\Lang;
use App\Core\MTTNotificationCenter;
use function FastRoute\simpleDispatcher;
use App\Controllers\HelloController;
use App\Controllers\HomeController;
use App\Controllers\ApiController;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/* ===================================================================================================================== */

if (getenv('MTT_ENABLE_DEBUG') == 'YES' || (defined('MTT_DEBUG') && MTT_DEBUG)) {
    if (!defined('MTT_DEBUG')) {
        define('MTT_DEBUG', true);
    }
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (!defined('MTT_DEBUG')) {
        define('MTT_DEBUG', false);
    }
}


/* ===================================================================================================================== */

define('MTT_API_USE_PATH_INFO', true);

/* ===================================================================================================================== */

Config::loadDatabaseConfig();

Config::configureDbConnection();

Config::load();

/* ===================================================================================================================== */

date_default_timezone_set(Config::get('timezone'));

/* ===================================================================================================================== */

//User can override language setting by cookies or query
$forceLang = '';

if (isset($_COOKIE['lang'])) {
    $forceLang = $_COOKIE['lang'];
}
//else if ( isset($_GET['lang']) ) $forceLang = $_GET['lang'];

if ($forceLang != '' && preg_match("/^[a-z-]+$/i", $forceLang)) {
    Config::set('lang', $forceLang);
    //TODO: special for demo, do not change config
}

Lang::loadLang(Config::get('lang'));

$lang = Lang::instance();

if ($lang->rtl()) {
    Config::set('rtl', 1);
}

/* ===================================================================================================================== */

if (Utility::needAuth() && !isset($dontStartSession)) {
    Utility::setupAndStartSession();
}
Utility::setNocacheHeaders();

if (Utility::accessToken() == '') {
    Utility::updateToken();
}

if (!defined('MTT_DISABLE_EXT')) {
    define('MTT_EXT', '../ext/');
    Utility::loadExtensions();
}

/* ===================================================================================================================== */

//Parse query string
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
    Route::parseRoute($_SERVER['QUERY_STRING']);
}

/* ===================================================================================================================== */

if (!is_int(Config::get('firstdayofweek')) || Config::get('firstdayofweek') < 0 || Config::get('firstdayofweek') > 6) {
    Config::set('firstdayofweek', 1);
}

/* ===================================================================================================================== */

$_mttinfo = array();

/* ===================================================================================================================== */

// create a log channel
$log = new Logger('Index');
$log->pushHandler(new StreamHandler('../Logs/MTT-Test-1.log', Level::Debug));

$log->pushProcessor(function ($record) {
    $record->extra['REQ_URI'] = $_SERVER['REQUEST_URI'];

    return $record;
});

// $log->warning('Foo');
// $log->error('Bar');

/* ===================================================================================================================== */

$dispatcher = simpleDispatcher(function (FastRoute\ConfigureRoutes $r) {
    $r->addRoute('GET', '/mytinytodo/', HomeController::class . '/index');
    $r->addRoute('GET', '/mytinytodo/api/{name:.+}', ApiController::class . '/index');
    $r->addRoute('GET', '/mytinytodo/api/{name}', ApiController::class . '/index');
    $r->addRoute('POST', '/mytinytodo/api/{name:.+}', ApiController::class . '/index');
    // $r->addRoute('GET', '/mytinytodo/Public/index.php/api', ApiController::class . '/index');
    // $r->addRoute('GET', '/mytinytodo/Public/', ApiController::class . '/index');
    // $r->addRoute('GET', '/mytinytodo/Public/{name}', ApiController::class . '/index');
    $r->addRoute('GET', '/mytinytodo/Hello', HelloController::class . '/index');

    // {id} must be a number (\d+)
    // $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
    // The /{title} suffix is optional
    // $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found!!!";
        $log->error('Dispatcher:404 Not Found', array_merge(['HTTPMethod' => $httpMethod], ['URI' => $uri], ['RouteInfo0' => $routeInfo[0]], ['RouteInfo1' => $routeInfo[1]]));
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        $log->error('Dispatcher:405 Not Allowed', array_merge(['HTTPMethod' => $httpMethod], ['URI' => $uri], ['RouteInfo0' => $routeInfo[0]], ['RouteInfo1' => $routeInfo[1]]));
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];

        $vars = $routeInfo[2];

        if (strpos($handler, 'ApiController') > 0) {
            $vars['name'] = '/' . (isset($vars['name']) ? $vars['name'] : '');
        }
        // ... call $handler with $vars

        list($class, $method) = explode("/", $handler, 2);

        $log->notice('Dispatcher:Found', array_merge(['HTTPMethod' => $httpMethod], ['URI' => $uri], ['Class' => $class], ['Method' => $method], ['Vars' => $vars], ['RouteInfo0' => $routeInfo[0]], ['RouteInfo1' => $routeInfo[1]], ['RouteInfo2' => $routeInfo[2]]));
        call_user_func_array(array(new $class(), $method), $vars);
        break;
}

/* ===================================================================================================================== */

MTTNotificationCenter::postDidFinishRequestNotification();

/* ===================================================================================================================== */
