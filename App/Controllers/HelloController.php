<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Monolog\Logger;

class HelloController extends BaseController
{
    protected Logger $log;

    public function __construct(Logger $logger)
    {
        $this->log = $logger;
        $this->log = $this->log->withName('HelloController');
    }

    // public function __construct()
    // {
    //     $this->log = new Logger('HelloController');
    //     $this->log->pushHandler(new StreamHandler('Logs/MTT-Test-1.log', Level::Debug));

    //     $this->log->pushProcessor(function ($record) {
    //         $record->extra['REQ_URI'] = $_SERVER['REQUEST_URI'];

    //         return $record;
    //     });
    // }

    // public function index()
    // {
    //     $request = new Request();
    //     $this->log->info('request created');
    //     $response = new Response($request);
    //     $this->log->info('response created with request');

    //     $response->setStatus(200);
    //     $this->log->info('response set status 200');
    //     $response->setHeader('Content-Type', 'text/html');

    //     $response->setBody(self::getView('hello', []));
    //     $this->log->info('response set body');
    //     $response->send();
    // }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('hello', ['name' => $request->getQueryParams()['name'] ?? '']));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }
}
