<?php

declare(strict_types=1);

namespace App\Controllers;

use App\API\ListsController;
use App\API\TasksController;
use App\API\TagsController;
use App\API\ExtSettingsController;
use App\API\AuthController;
use App\Core\MTTExtensionLoader;
use App\Core\MTTHttpApiExtender;
use App\API\ApiRequest;
use App\API\ApiResponse;
use App\Database\DBConnection;
use App\Utility;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;
use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

// if (!defined('MTTPATH')) {
//     define('MTTPATH', dirname(__FILE__) . '/');
// }

// if (!defined('MTTINC')) {
//     define('MTTINC', MTTPATH . 'includes/');
// }

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// require_once('init.php');

// if (MTT_DEBUG) {
//     set_error_handler('myErrorHandler'); //catch Notices, Warnings
//     set_exception_handler('myExceptionHandler');
// }
// else {
//     ini_set('display_errors', '0');
// }

// require_once(MTTINC. 'api/ListsController.php');
// require_once(MTTINC. 'api/TasksController.php');
// require_once(MTTINC. 'api/TagsController.php');
// require_once(MTTINC. 'api/AuthController.php');
// require_once(MTTINC. 'api/ExtSettingsController.php');

class ApiController
{
    private Logger $log;

    public function __construct(Logger $logger)
    {
        $this->log = $logger->withName('ApiController');
    }


    public function index(ServerRequestInterface $request, array $args): ResponseInterface
    {
        if (Utility::accessToken() == '') {
            Utility::updateToken();
        }

        $this->log->info('Args Dump', [$args]);
        $this->log->info('League Request Query String', [$request->getQueryParams()]);

        $endpoints = array(
            '/mytinytodo/api/lists' => [
                'GET'  => [ ListsController::class , 'get' ],
                'POST' => [ ListsController::class , 'post' ],
                'PUT'  => [ ListsController::class , 'put' ],
            ],
            '/lists/(-?\d+)' => [
                'GET'     => [ ListsController::class , 'getId' ],
                'PUT'     => [ ListsController::class , 'putId' ],
                'DELETE'  => [ ListsController::class , 'deleteId' ],
                'POST'    => [ ListsController::class , 'putId' ], //compatibility
            ],
            '/mytinytodo/api/tasks' => [
                'GET'  => [ TasksController::class , 'get' ],
                'POST' => [ TasksController::class , 'post' ],
                'PUT'  => [ TasksController::class , 'put' ],
            ],
            '/tasks/(-?\d+)' => [
                'PUT'     => [ TasksController::class , 'putId' ],
                'DELETE'  => [ TasksController::class , 'deleteId' ],
                'POST'    => [ TasksController::class , 'putId' ], //compatibility
            ],
            '/tasks/parseTitle' => [
                'POST' => [ TasksController::class , 'postTitleParse' ],
            ],
            '/mytinytodo/api/tasks/newCounter' => [
                'POST' => [ TasksController::class , 'postNewCounter' ],
            ],
            '/mytinytodo/api/tagCloud/(-?\d+)' => [
                'GET'  => [ TagsController::class , 'getCloud' ],
            ],
            '/suggestTags' => [
                'GET'  => [ TagsController::class , 'getSuggestions' ],
            ],
            '/(login|logout|session)' => [
                'POST' => [ AuthController::class , 'postAction' ],
            ],
            '/ext-settings/(.+)' => [
                'GET'     => [ ExtSettingsController::class , 'get' ],
                'PUT'     => [ ExtSettingsController::class , 'put' ],
                'POST'    => [ ExtSettingsController::class , 'put' ], //compatibility
            ]
        );

        // look for extensions
        foreach (MTTExtensionLoader::loadedExtensions() as $instance) {
            $this->log->info('inside foreach MTTExtensionLoader::loadedExtensions() as $instance');
            if ($instance instanceof MTTHttpApiExtender) {
                $this->log->info('$instance instanceof MTTHttpApiExtender is TRUE');
                $newRoutes = $instance->extendHttpApi();
                $this->log->notice('instance->extendHttpApi', ['newRoutes' => $newRoutes]);
                foreach ($newRoutes as $endpoint => $methods) {
                    $endpoint = '/ext/' . $instance::BUNDLEID . $endpoint;
                    foreach ($methods as $k => &$v) {
                        $v[3] = true; // Mark extension method
                    }
                    $endpoints[$endpoint] = $methods;
                }
            }
        }

        $tempString = (count($request->getQueryParams()) > 0) ? '/mytinytodo/api/tasks&list=1&' . http_build_query($request->getQueryParams()) : $request->getUri()->getPath() . ((isset($args[0])) ? '/' . $args[0] : "");
        $req = new ApiRequest($tempString);
        $this->log->info('Argument sent to MTT Request', ['path' => $tempString]);
        $this->log->info('MTT Request Created');
        $response = new ApiResponse();
        $executed = false;
        $data = null;

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        /* ===================================================================================================================== */


        foreach ($endpoints as $search => $methods) {
            $m = array();
            if (preg_match("#^$search$#", $req->path, $m)) {
                $this->log->notice('preg_match found', array_merge(['search' => $search], ['Req Path' => $req->path], ['m' => $m]));
                $classDescr = $methods[$req->method] ?? null;
                $this->log->info('methods[req->method] assigned to classDescr', ['ClassDescr' => $classDescr]);
                // check if http method is supported for path
                if (is_null($classDescr)) {
                    $this->log->warning('ClassDescr is NULL, Response exit');
                    $response->htmlContent("Unknown method for resource", 500)
                        ->exit();
                }
                if (!is_array($classDescr) || count($classDescr) < 2) {
                    $this->log->warning('ClassDescr is not an array or count < 2', ['classDescr' => $classDescr]);
                    $response->htmlContent("Incorrect method definition", 500)
                        ->exit();
                }
                // check if class method exists
                $class = $classDescr[0];
                $classMethod = $classDescr[1];
                $isExtMethod = $classDescr[3] ?? false;
                $this->log->info('Assigned Class, ClassMethod, and isExtMethod', array_merge(['class' => $class], ['ClassMethod' => $classMethod], ['isExtMethod' => $isExtMethod]));

                if ($isExtMethod) {
                    if (false == ($classDescr[2] ?? false)) { //TODO: describe $classDescr[2]
                        $this->log->notice('CheckWriteAccess since isExtMEthod TRUE');
                        // By default all extension methods require write access rights
                        self::checkWriteAccess();
                    }
                }

                $param = null;
                if (count($m) >= 2) {
                    $param = $m[1];
                    $this->log->info('Param assigned since count m >= 2', ['param' => $param]);
                }

                // if (is_null($class) && is_null($classMethod) && (count($request->getQueryParams()) > 0)) {
                //     $class = TasksController::class;
                //     $classMethod = 'get';
                // }

                if (method_exists($class, $classMethod)) { // test for static with ReflectionMethod?
                    $this->log->info('method exists', array_merge(['class' => $class], ['classMethod' => $classMethod]));
                    if ($req->method != 'GET' && $req->contentType == 'application/json') {
                        $this->log->info('method exists: != GET and JSON', array_merge(['Req Method' => $req->method], ['Req ContentType' => $req->contentType]));
                        if ($req->decodeJsonBody() === false) {
                            $this->log->error('Req decode JSON body is false');
                            $response->htmlContent("Failed to parse JSON body", 500)
                                ->exit();
                        }
                    }

                    // $instance = new $class($req, $response);
                    // $instance->$classMethod($param);

                    $tempObj = new $class($req, $response);
                    call_user_func([$tempObj, $classMethod]);

                    $executed = true;

                    $this->log->info('just called call user func. Break is next');
                    break;
                } else {
                    if (MTT_DEBUG) {
                        $this->log->error('Class Method not Found MTT DEBUG TRUE', array_merge(['class' => $class], ['classMethod' => $classMethod]));
                        $response->htmlContent("Class method $class:$classMethod() not found", 405)->exit();
                    }
                    $this->log->error('Class Method not Found MTT DEBUG FALSE', array_merge(['class' => $class], ['classMethod' => $classMethod]));
                    $response->htmlContent("Class method not found", 405)->exit();
                }
            }
        }

        if (!$executed) {
            if (MTT_DEBUG) {
                $this->log->error('Unknown Endpoint MTT DEBUG TRUE', array_merge(['class' => $class], ['classMethod' => $classMethod], ['Req Method' => $req->method], ['Req Path' => $req->path]));
                $response->htmlContent("Unknown endpoint: {$req->method} {$req->path}", 404)->exit();
            }
            $this->log->error('Unknown Endpoint MTT DEBUG FALSE', array_merge(['class' => $class], ['classMethod' => $classMethod], ['Req Method' => $req->method], ['Req Path' => $req->path]));
            $response->htmlContent("Unknown endpoint", 404)->exit();
        }
        $this->log->info('Response exit called. Looks like executed');
        // $response->exit();

        $responseBody = $psr17Factory->createStream(json_encode($response->data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }

    /* ===================================================================================================================== */

    public static function myErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($errno == E_ERROR || $errno == E_CORE_ERROR || $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR || $errno == E_PARSE) {
            $error = 'Error';
        } elseif ($errno == E_WARNING || $errno == E_CORE_WARNING || $errno == E_COMPILE_WARNING || $errno == E_USER_WARNING || $errno == E_STRICT) {
            if (error_reporting() & $errno) {
                $error = 'Warning';
            } else {
                return;
            }
        } elseif ($errno == E_NOTICE || $errno == E_USER_NOTICE || $errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
            if (error_reporting() & $errno) {
                $error = 'Notice';
            } else {
                return;
            }
        } else {
            $error = "Error ($errno)"; // here may be E_RECOVERABLE_ERROR
        }
        throw new Exception("$error: '$errstr' in $errfile:$errline", -1);
    }

    /* ===================================================================================================================== */

    public static function myExceptionHandler(Throwable $e)
    {
        // to avoid Exception thrown without a stack frame
        try {
            if (-1 == $e->getCode()) {
                //thrown in myErrorHandler
                http_response_code(500);
                Utility::logAndDie($e->getMessage());
            }

            $c = get_class($e);
            $errText = "Exception ($c): '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine();

            if (MTT_DEBUG) {
                if (count($e->getTrace()) > 0) {
                    $errText .= "\n" . $e->getTraceAsString() ;
                }
            }
            http_response_code(500);
            Utility::logAndDie($errText);
        } catch (Exception $e) {
            http_response_code(500);
            Utility::logAndDie('Exception in ExceptionHandler: \'' . $e->getMessage() . '\' in ' . $e->getFile() . ':' . $e->getLine());
        }
        exit;
    }

    /* ===================================================================================================================== */

    public static function checkReadAccess(?int $listId = null)
    {
        Utility::checkToken();
        $db = DBConnection::instance();
        if (Utility::isLogged()) {
            return true;
        }

        if ($listId !== null) {
            $id = $db->sq("SELECT id FROM {$db->prefix}lists WHERE id=? AND published=1", array($listId));
            if ($id) {
                return;
            }
        }
        Utility::jsonExit(array('total' => 0, 'list' => array(), 'denied' => 1));
    }

    public static function checkWriteAccess(?int $listId = null)
    {
        Utility::checkToken();
        if (self::haveWriteAccess($listId)) {
            return;
        }

        http_response_code(403);

        Utility::jsonExit(array('total' => 0, 'list' => array(), 'denied' => 1));
    }

    public static function haveWriteAccess(?int $listId = null): bool
    {
        if (Utility::isReadonly()) {
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
}
