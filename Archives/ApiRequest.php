<?php

declare(strict_types=1);

namespace App\API;

class ApiRequest
{
    public $path;
    public $method;
    public $contentType;
    public $jsonBody;

    public function __construct(string $name)
    {
        if (defined('MTT_API_USE_PATH_INFO')) {
            // $this->path = $_SERVER['REQUEST_URI'];
            $this->path = $name;
            // die($this->path);
        } else {
            $this->path = $_GET['_path'] ?? '';
        }
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function decodeJsonBody()
    {
        $this->jsonBody = json_decode(file_get_contents('php://input'), true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
        return $this->jsonBody;
    }
}
