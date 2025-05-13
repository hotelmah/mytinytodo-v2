<?php

namespace App\Controllers;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class HelloController extends BaseController
{
    private Logger $log;

    public function __construct()
    {
        $this->log = new Logger('HelloController');
        $this->log->pushHandler(new StreamHandler('../Logs/MTT-Test-1.log', Level::Debug));

        $this->log->pushProcessor(function ($record) {
            $record->extra['REQ_URI'] = $_SERVER['REQUEST_URI'];

            return $record;
        });
    }

    public function index()
    {
        $request = new Request();
        $this->log->info('request created');
        $response = new Response($request);
        $this->log->info('response created with request');

        $response->setStatus(200);
        $this->log->info('response set status 200');
        $response->setHeader('Content-Type', 'text/html');

        $response->setBody(self::getView('hello', []));
        $this->log->info('response set body');
        $response->send();
    }
}
