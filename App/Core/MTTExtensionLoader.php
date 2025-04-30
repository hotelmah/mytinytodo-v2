<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Core;

use Exception;
use App\Lang\Lang;

class MTTExtensionLoader
{
    private static $exts = [];

    public static function loadExtension(string $ext)
    {
        if (isset(self::$exts[$ext])) {
            error_log("Extension '$ext' is already registered");
            return;
        }

        $loader = ''; // MTT_EXT . $ext . '/loader.php';
        if (!file_exists($loader)) {
            error_log("Failed to init extension '$ext': no loader.php");
            return;
        }

        require_once(MTT_EXT . $ext . '/loader.php');
        $getInstance = 'mtt_ext_' . $ext . '_instance';

        if (!function_exists($getInstance)) {
            throw new Exception("Failed to init extension '$ext': no '$getInstance' function");
        }

        $instance = $getInstance();
        if (!($instance instanceof MTTExtension)) {
            throw new Exception("Failed to init extension '$ext': incompatible instance");
        }

        $className = get_class($instance);
        if (!defined("$className::BUNDLEID")) {
            throw new Exception("Failed to load extension '$ext': missing required class constants (BUNDLEID)");
        }
        if ($instance::BUNDLEID != $ext) {
            throw new Exception("Failed to load extension '$ext': BUNDLEID does not conforms to extension dir");
        }

        Lang::instance()->loadExtensionLang($ext);

        $instance->init();
        self::$exts[$ext] = $instance;
    }

    /**
     * @return MTTExtension[]
     */
    public static function loadedExtensions(): array
    {
        $a = [];
        foreach (self::$exts as $ext => $instance) {
            $a[] = $instance;
        }
        return $a;
    }

    /**
     * @return string[]
     */
    public static function bundles(): array
    {
        $lang = Lang::instance();
        $a = [];
        $files = array_diff(scandir(MTT_EXT) ?? [], ['.', '..']);
        foreach ($files as $ext) {
            if (
                !is_dir(MTT_EXT . $ext)
                || !file_exists(MTT_EXT . $ext . '/loader.php')
            ) {
                continue;
            }

            $meta = MTTExtension::extMetaInfo($ext);
            if (!$meta) {
                continue;
            }

            if (
                $lang->langCode() != 'en'
                && null !== ($translation = $lang->getExtensionLang($ext))
                && null !== ($locName = $translation['ext.' . $ext . '.name'] ?? null)
            ) {
                $meta['name'] = $locName;
            }
            $a[$ext] = $meta;
        }
        return $a;
    }

    public static function extensionInstance(string $ext): ?MTTExtension
    {
        return self::$exts[$ext] ?? null;
    }

    public static function isLoaded(string $ext): bool
    {
        return isset(self::$exts[$ext]);
    }
}
