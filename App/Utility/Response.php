<?php

declare(strict_types=1);

namespace App\Utility;

use App\Core\MTTNotificationCenter;

class Response
{
    public static function jsonExit($data)
    {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        MTTNotificationCenter::postDidFinishRequestNotification();
        exit;
    }

    public static function logAndDie($userText, $errText = null)
    {
        $errText === null ? error_log($userText) : error_log($errText);
        if (ini_get('display_errors')) {
            echo htmlspecialchars($userText);
        } else {
            echo "Error! See details in error log.";
        }
        exit(1);
    }
}
