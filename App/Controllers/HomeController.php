<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

class HomeController extends BaseController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('default', []));
        return $psr17Factory->createResponse(200)->withBody($responseBody);
    }

    public function settings(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('settings', []));
        return $psr17Factory->createResponse(200)->withBody($responseBody);
    }
}
