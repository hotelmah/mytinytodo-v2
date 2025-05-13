<?php

declare(strict_types=1);

namespace App\API;

use App\Utility;

class ApiResponse
{
    public ?array $data = null;
    public string $content = "";
    public string $contentType = 'application/json';
    public ?int $code = null;

    public function content(string $contentType, string $content, int $code = 200)
    {
        $this->contentType = $contentType;
        $this->content = $content;
        $this->code = $code;
        return $this;
    }

    public function htmlContent(string $content, int $code = 200): ApiResponse
    {
        return $this->content('text/html', $content, $code);
    }

    public function cssContent(string $content, int $code = 200): ApiResponse
    {
        return $this->content('text/css', $content, $code);
    }

    public function exit()
    {
        if (is_null($this->data) && is_null($this->code)) {
            http_response_code(404);
        }
        if (!is_null($this->code)) {
            http_response_code($this->code);
        }
        if ($this->contentType != 'application/json') {
            header('Content-type: ' . $this->contentType);

            if (!is_null($this->data)) {
                print $this->data;
            }
            exit();
        }
        Utility::jsonExit($this->data);
    }
}
