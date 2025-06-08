<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Config\Config;
use App\Utility\Security;
use App\Utility\Authentication;
use monolog\Logger;

class AuthController extends BaseControllerApi
{
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);
        $this->log = $this->log->withName('AuthController');
    }

    public function postAction(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $this->log->info('Request', ['getParsedBody' => $request->getParsedBody()]);
        if (isset($request->getParsedBody()['password'])) {
            $args['password'] = $request->getParsedBody()['password'];
        }

        $action = $args['adminoptions'] ?? '';
        $this->log->info("AuthController postAction called with action: {$action}");

        switch ($action) {
            case 'login':
                $this->log->info("Login attempt with password: " . (isset($args['password']) ? '***' : 'not provided'));
                $data = $this->login(isset($args['password']) ? $args['password'] : '');
                break;
            case 'logout':
                $data = $this->logout();
                break;
            case 'session':
                $data = $this->createSession();
                break;
            default:
                $data = ['total' => 0]; // error 400 ?
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }

    private function login(string $password): ?array
    {
        Authentication::checkToken();
        $t = array('logged' => 0);

        if (!Authentication::needAuth()) {
            $t['disabled'] = 1;
            return $t;
        }

        if (Security::isPasswordEqualsToHash($password, Config::get('password'))) {
            Authentication::updateSessionLogged(true);
            $t['token'] = Authentication::updateToken();
            $t['logged'] = 1;
        }

        return $t;
    }

    private function logout(): ?array
    {
        Authentication::checkToken();
        Authentication::updateSessionLogged(false);
        Authentication::updateToken();

        session_regenerate_id(true);
        $t = array('logged' => 0);

        return $t;
    }

    private function createSession(): ?array
    {
        $t = array();

        if (!Authentication::needAuth()) {
            $t['disabled'] = 1;
            return $t;
        }

        if (Authentication::accessToken() == '') {
            Authentication::updateToken();
        }

        $t['token'] = Authentication::accessToken();
        $t['session'] = session_id();

        return $t;
    }
}
