<?php

declare(strict_types=1);

namespace App\Core;

use App\Utility\Info;

abstract class MTTExtension
{
    const BUNDLEID = ''; //abstract

    public function init()
    {
        // ...
    }

    public static function extMetaInfo(string $ext): ?array
    {
        $file = MTT_EXT . $ext . '/extension.json';
        if (
            file_exists($file)
            && false !== ($json = file_get_contents($file))
            && ($meta = json_decode($json, true))
            && is_array($meta)
        ) {
            // check mandatory keys
            if (!isset($meta['BUNDLEID']) || !isset($meta['name']) || !isset($meta['version']) || !isset($meta['description'])) {
                return null;
            }
            if (!is_string($meta['BUNDLEID']) || !is_string($meta['name']) || !is_string($meta['version']) || !is_string($meta['description'])) {
                return null;
            }
            return $meta;
        }
        error_log("$ext/extension.json is missing or invalid");
        return null;
    }

    public static function extApiActionUrl(string $action, ?string $params = null)
    {
        $url = Info::getUnsafeMttinfo('api_url') . 'ext/' . static::BUNDLEID . "/$action";
        if (!is_null($params)) {
            if (false !== strpos($url, '?')) {
                $url .= '&' . $params;
            } else {
                $url .= '?' . $params;
            }
        }
        return $url;
    }
}
