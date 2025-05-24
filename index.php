<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once('vendor/autoload.php');

use App\Route;
use App\Config\Config;
use App\Utility;
use App\Lang\Lang;
use App\Core\MTTNotificationCenter;

use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;

use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\ForbiddenException;

use Symfony\Component\HttpFoundation\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

use App\Controllers\HelloController;
use App\Controllers\HomeController;
use App\Controllers\ApiController;
use App\API\TasksController;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use League\Container\Container;
use League\Route\Http\Exception\MethodNotAllowedException as ExceptionMethodNotAllowedException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

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
$log->pushHandler(new StreamHandler('Logs/MTT-Test-1.log', Level::Debug));

$log->pushProcessor(function ($record) {
    $record->extra['REQ_METHOD'] = $_SERVER['REQUEST_METHOD'];
    $record->extra['REQ_URI'] = $_SERVER['REQUEST_URI'];

    return $record;
});

/* ===================================================================================================================== */

$container = new Container();

/* ===================================================================================================================== */

$container->add(ApiController::class)->addArgument($log);
$container->add(TasksController::class)->addArgument($log);
// $container->add(Basic::class)->addArgument('Gee');
// $container->add(Logger::class)->addArgument('hello');

/* ===================================================================================================================== */

$symfonyRequest = Request::createFromGlobals();

$psr17Factory = new Psr17Factory();
$psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$psrRequest = $psrHttpFactory->createRequest($symfonyRequest);
$log->info('Request Created');

/* ===================================================================================================================== */

$strategy = new ApplicationStrategy();
$strategy->setContainer($container);

$router = new Router();
$log->info('Created Router');
$router->setStrategy($strategy);
$log->info('Added Strategy to Router for Dependency Injection');


// map a route
$router->map('GET', '/mytinytodo/', [HomeController::class, 'index']);
$router->map('GET', '/mytinytodo/api/lists', [ApiController::class, 'index']);
$router->map('GET', '/mytinytodo/api', [ApiController::class, 'index']);
$router->map('POST', '/mytinytodo/api/tasks/newCounter', [ApiController::class, 'index']);
$router->map('POST', '/mytinytodo/api/tasks', [ApiController::class, 'index']);
$router->map('GET', '/mytinytodo/api/tagCloud/{id}', [ApiController::class, 'index']);
$router->map('GET', '/mytinytodo/hello', [HelloController::class, 'index']);

try {
    $psrResponse = $router->dispatch($psrRequest);
    $log->notice('Router Dispatch: Found');
} catch (NotFoundException $e) {
    echo '<h1>' . $e->getStatusCode() . '</h1>';
    echo '<h2>' . $e->getMessage() . '</h2>';
    echo '<pre>';
    print_r($e->getHeaders());
    echo '</pre>';
    $log->error('404 Not Found Exception Occurred', $e->getHeaders());
    exit();
} catch (ForbiddenException $e) {
    echo '<h1>' . $e->getStatusCode() . '</h1>';
    echo '<h2>' . $e->getMessage() . '</h2>';
    echo '<pre>';
    print_r($e->getHeaders());
    echo '</pre>';
    $log->error('Forbidden Exception Occurred', $e->getHeaders());
    exit();
} catch (ExceptionMethodNotAllowedException $e) {
    echo '<h1>' . $e->getStatusCode() . '</h1>';
    echo '<h2>' . $e->getMessage() . '</h2>';
    $log->error('Get Status Code', ['GetStatusCode' => $e->getStatusCode()]);
    $log->error('Get Message', ['GetMessage' => $e->getMessage()]);
    echo '<pre>';
    print_r($e->getHeaders());
    echo '</pre>';
    $log->error('Method Not Allowed Exception Occurred', $e->getHeaders());
    exit();
}

$log->info('Route Dispatch Completed');

/* ===================================================================================================================== */

$httpFoundationFactory = new HttpFoundationFactory();
$symfonyResponse = $httpFoundationFactory->createResponse($psrResponse);

$symfonyResponse->headers->add(['Content-Type' => 'text/html']);
$symfonyResponse->send();
$log->notice('Symfony Response Sent. END');

/* ===================================================================================================================== */

MTTNotificationCenter::postDidFinishRequestNotification();

/* ===================================================================================================================== */
