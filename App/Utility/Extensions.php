<?php

declare(strict_types=1);

namespace App\Utility;

use App\Config\Config;
use App\Core\MTTExtensionLoader;
use Exception;

/**
 * Extensions utility class to load extensions based on configuration.
 */
class Extensions
{
    public static function loadExtensions()
    {
        $a = Config::get('extensions');
        if (!$a || !is_array($a)) {
            return;
        }
        foreach ($a as $ext) {
            if (is_string($ext)) {
                try {
                    MTTExtensionLoader::loadExtension($ext);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }
}
