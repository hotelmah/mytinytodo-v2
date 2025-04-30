<?php

namespace App\Controllers;

use Framework\HTTP\Request;
use Framework\HTTP\Response;

class HelloController extends BaseController
{
    public function index()
    {
        $request = new Request();
        $response = new Response($request);

        $response->setStatus(200);
        $response->setHeader('Content-Type', 'text/html');

        $response->setBody(self::getView('hello', []));
        $response->send();
    }
}
