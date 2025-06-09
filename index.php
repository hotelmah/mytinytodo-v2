<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once('vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

use App\Controllers\HomeController;
use App\Controllers\ListsController;
use App\Controllers\TasksController;
use App\Controllers\TagsController;
use App\Controllers\AuthController;
use App\Controllers\ExtSettingsController;
use App\Controllers\HelloController;

use League\Container\Container;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;

use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\MethodNotAllowedException as ExceptionMethodNotAllowedException;

use App\Config\Config;
use App\Utility\Authentication;
use App\Utility\Http;
use App\Utility\Extensions;
use App\Core\MTTNotificationCenter;
use App\Lang\Lang;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

/* ===================================================================================================================== */

if (getenv('MTT_ENABLE_DEBUG') == 'YES' || (defined('MTT_DEBUG') && MTT_DEBUG)) {
    if (!defined('MTT_DEBUG')) {
        define('MTT_DEBUG', true);
    }
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');

    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    if (!defined('MTT_DEBUG')) {
        define('MTT_DEBUG', false);
    }
}

/* ===================================================================================================================== */

if ($_SERVER['REQUEST_URI'] != '/mytinytodo/setup') {
    Config::dbInitialize();
}

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

if (Authentication::needAuth() && !isset($dontStartSession)) {
    Authentication::setupAndStartSession();
}

if (Authentication::accessToken() == '') {
    Authentication::updateToken();
}

Http::setNocacheHeaders();

/* ===================================================================================================================== */

if (!defined('MTT_DISABLE_EXT')) {
    define('MTT_EXT', 'ext/');
    Extensions::loadExtensions();
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
$log->pushHandler(new StreamHandler('Logs/MyTinyTodo-' . date('Y-m-d') . '.log', Level::Debug));
$log->pushHandler(new NullHandler());

$log->pushProcessor(function ($record) {
    $record->extra['REQ_METHOD'] = $_SERVER['REQUEST_METHOD'];
    $record->extra['REQ_URI'] = $_SERVER['REQUEST_URI'];

    return $record;
});

/* ===================================================================================================================== */

$container = new Container();

/* ===================================================================================================================== */

$container->add(ListsController::class)->addArgument($log);
$container->add(TasksController::class)->addArgument($log);
$container->add(TagsController::class)->addArgument($log);
$container->add(AuthController::class)->addArgument($log);
$container->add(ExtSettingsController::class)->addArgument($log);
$container->add(HelloController::class)->addArgument($log);

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
$router->map('GET', '/mytinytodo', [HomeController::class, 'redirect']);

$router->map('POST', '/mytinytodo/api/tasks/parseTitle', [TasksController::class, 'postTitleParse']);

$router->map('POST', '/mytinytodo/api/tasks/newCounter', [TasksController::class, 'postNewCounter']);

$router->map('GET', '/mytinytodo/api/suggestTags', [TagsController::class, 'getSuggestions']);

$router->map('GET', '/mytinytodo/api/lists/{id:-?\d+}', [ListsController::class, 'getId']);
$router->map('PUT', '/mytinytodo/api/lists/{id:-?\d+}', [ListsController::class, 'putId']);
$router->map('DELETE', '/mytinytodo/api/lists/{id:-?\d+}', [ListsController::class, 'deleteId']);
$router->map('POST', '/mytinytodo/api/lists/{id:-?\d+}', [ListsController::class, 'putId']);

$router->map('GET', '/mytinytodo/api/lists', [ListsController::class, 'get']);
$router->map('POST', '/mytinytodo/api/lists', [ListsController::class, 'post']);
$router->map('PUT', '/mytinytodo/api/lists', [ListsController::class, 'put']);

$router->map('PUT', '/mytinytodo/api/tasks/{id:-?\d+}', [TasksController::class, 'putId']);
$router->map('DELETE', '/mytinytodo/api/tasks/{id:-?\d+}', [TasksController::class, 'deleteId']);
$router->map('POST', '/mytinytodo/api/tasks/{id:-?\d+}', [TasksController::class, 'putId']);

$router->map('GET', '/mytinytodo/api/tasks', [TasksController::class, 'get']);
$router->map('POST', '/mytinytodo/api/tasks', [TasksController::class, 'post']);
$router->map('PUT', '/mytinytodo/api/tasks', [TasksController::class, 'put']);

$router->map('GET', '/mytinytodo/api/tagCloud/{id:-?\d+}', [TagsController::class, 'getCloud']);

$router->map('GET', '/mytinytodo/settings', [HomeController::class, 'settings']);
$router->map('POST', '/mytinytodo/settings', [HomeController::class, 'settings']);

$router->addPatternMatcher('adminoptions', '(login|logout|session)');
$router->map('POST', '/mytinytodo/api/{adminoptions}', [AuthController::class, 'postAction']);

$router->addPatternMatcher('extsettingsopt', '(.+)');
$router->map('GET', '/mytinytodo/api/ext-settings/{extsettingsopt}', [ExtSettingsController::class, 'get']);
$router->map('PUT', '/mytinytodo/api/ext-settings/{extsettingsopt}', [ExtSettingsController::class, 'put']);
$router->map('POST', '/mytinytodo/api/ext-settings/{extsettingsopt}', [ExtSettingsController::class, 'put']);

$router->map('GET', '/mytinytodo/setup', [HomeController::class, 'setup']);
$router->map('POST', '/mytinytodo/setup', [HomeController::class, 'setup']);
$router->map('GET', '/mytinytodo/export', [HomeController::class, 'export']);
$router->map('GET', '/mytinytodo/feed', [HomeController::class, 'feed']);

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
