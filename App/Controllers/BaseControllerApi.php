<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utility\Authentication;
use monolog\Logger;

abstract class BaseControllerApi
{
    protected Logger $log;

    public function __construct(Logger $logger)
    {
        $this->log = $logger;

        // if (Authentication::accessToken() == '') {
        //     Authentication::updateToken();
        // }
    }
}
