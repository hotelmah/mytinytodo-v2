<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utility\Http;

abstract class BaseController
{
    protected string $URIPrefix;

    public function __construct()
    {
        $this->URIPrefix = Http::getURIPrefix();
    }

    protected static function getView(string $view, array $data = [])
    {
        extract($data);

        ob_start();
        require_once("App/Views/$view.php");
        return ob_get_clean();
    }
}
