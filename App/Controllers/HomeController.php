<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Utility\Http;

class HomeController extends BaseController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('default', ['URIPrefix' => Http::getURIPrefix()]));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function redirect(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        return $psr17Factory->createResponse(301)->withHeader('location', $this->URIPrefix . '/')->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function settings(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('settings', []));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function setup(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $psr17Factory->createStream(self::getView('setup', []));
        return $psr17Factory->createResponse(302)->withHeader('location', $this->URIPrefix . '/setup')->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function export(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('export', ['list' => $request->getQueryParams()['list'], 'format' => $request->getQueryParams()['format']]));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public function feed(ServerRequestInterface $request): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(self::getView('feed', ['list' => $request->getQueryParams()['list']]));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }
}
