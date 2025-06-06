<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\API;

use App\Utility\Security;
use App\Utility\Authentication;
use App\Config\Config;

class AuthController extends ApiRequestResponse
{
    public function postAction($action)
    {
        switch ($action) {
            case 'login':
                $this->response->data = $this->login();
                break;
            case 'logout':
                $this->response->data = $this->logout();
                break;
            case 'session':
                $this->response->data = $this->createSession();
                break;
            default:
                $this->response->data = ['total' => 0]; // error 400 ?
        }
    }

    private function login(): ?array
    {
        Authentication::checkToken();
        $t = array('logged' => 0);
        if (!Authentication::needAuth()) {
            $t['disabled'] = 1;
            return $t;
        }
        $password = $this->req->jsonBody['password'] ?? '';
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
