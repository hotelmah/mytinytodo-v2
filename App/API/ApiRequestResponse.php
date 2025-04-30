<?php

declare(strict_types=1);

namespace App\API;

abstract class ApiRequestResponse
{
    /** @var ApiRequest */
    protected object $req;

    /** @var ApiResponse */
    protected object $response;

    public function __construct(ApiRequest $req, ApiResponse $response)
    {
        $this->req = $req;
        $this->response = $response;
    }
}
