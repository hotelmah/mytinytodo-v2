<?php

declare(strict_types=1);

namespace App\Controllers;

class BaseController
{
    protected static function getView(string $view, array $data = [])
    {
        extract($data);

        ob_start();
        require_once("../App/Views/$view.php");
        return ob_get_clean();
    }
}
