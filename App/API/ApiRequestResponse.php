<?php

declare(strict_types=1);

namespace App\API;

use monolog\Logger;

abstract class ApiRequestResponse
{
    /** @var ApiRequest */
    protected object $req;

    /** @var ApiResponse */
    protected object $response;

    protected Logger $log;

    public function __construct(ApiRequest $req, ApiResponse $response, Logger $logger)
    {
        $this->req = $req;
        $this->response = $response;
        $this->log = $logger;
        // $this->log = $logger->withName('TasksController');
    }
}
