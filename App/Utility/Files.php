<?php

declare(strict_types=1);

namespace App\Utility;

use App\Utility\Info;

class Files extends Info
{
    /**
     * Returns the file version based on the last modified time of a file.
     * If MTT_DEBUG is false, it returns the version from mttinfo.
     * If the file does not exist, it returns a not-found message.
     * If the file cannot be accessed, it returns a no-access message.
     *
     * @param string $dir The directory type ('content' or 'theme').
     * @param string $filename The name of the file to check.
     * @return void
     */

    public static function filever(string $dir, string $filename)
    {
        if (!MTT_DEBUG) {
            return self::mttinfo('version');
        }
        $prefix = self::getMttinfo('version') . '-' . time();
        $path = null;
        if ($dir == 'content') {
            $path = MTTPATH . 'content/';
        } else if ($dir == 'theme') {
            $path = MTTPATH . '/';
        } else {
            return print($prefix . '-unknown');
        }
        $fullPath = $path . $filename;
        if (!file_exists($fullPath)) {
            return print($prefix . '-not-found');
        }
        $mtime = filemtime($fullPath);
        if ($mtime === false) {
            return print($prefix . '-no-access');
        }
        return print($mtime);
    }
}
