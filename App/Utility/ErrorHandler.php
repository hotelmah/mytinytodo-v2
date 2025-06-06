<?php

declare(strict_types=1);

namespace App\Utility;

use Exception;
use Throwable;

class ErrorHandler
{
    public static function myErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($errno == E_ERROR || $errno == E_CORE_ERROR || $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR || $errno == E_PARSE) {
            $error = 'Error';
        } elseif ($errno == E_WARNING || $errno == E_CORE_WARNING || $errno == E_COMPILE_WARNING || $errno == E_USER_WARNING) {
            if (error_reporting() & $errno) {
                $error = 'Warning';
            } else {
                return;
            }
        } elseif ($errno == E_NOTICE || $errno == E_USER_NOTICE || $errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
            if (error_reporting() & $errno) {
                $error = 'Notice';
            } else {
                return;
            }
        } else {
            $error = "Error ($errno)"; // here may be E_RECOVERABLE_ERROR
        }
        throw new Exception("$error: '$errstr' in $errfile:$errline", -1);
    }

    /* ===================================================================================================================== */

    public static function myExceptionHandler(Throwable $e)
    {
        // to avoid Exception thrown without a stack frame
        try {
            if (-1 == $e->getCode()) {
                //thrown in myErrorHandler
                http_response_code(500);
                Response::logAndDie($e->getMessage());
            }

            $c = get_class($e);
            $errText = "Exception ($c): '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine();

            if (MTT_DEBUG) {
                if (count($e->getTrace()) > 0) {
                    $errText .= "\n" . $e->getTraceAsString() ;
                }
            }
            http_response_code(500);
            Response::logAndDie($errText);
        } catch (Exception $e) {
            http_response_code(500);
            Response::logAndDie('Exception in ExceptionHandler: \'' . $e->getMessage() . '\' in ' . $e->getFile() . ':' . $e->getLine());
        }
        exit;
    }
}
