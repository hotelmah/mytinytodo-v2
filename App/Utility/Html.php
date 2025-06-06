<?php

declare(strict_types=1);

namespace App\Utility;

class Html
{
    public static function htmlarray($a, $exclude = null)
    {
        self::htmlarrayRef($a, $exclude);
        return $a;
    }

    public static function htmlarrayRef(&$a, $exclude = null)
    {
        if (!$a) {
            return;
        }
        if (!is_array($a)) {
            $a = htmlspecialchars($a);
            return;
        }
        reset($a);
        if ($exclude && !is_array($exclude)) {
            $exclude = array($exclude);
        }
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $a[$k] = self::htmlarray($v, $exclude);
            } elseif (!$exclude) {
                $a[$k] = htmlspecialchars((string)$v ?? '');
            } elseif (!in_array($k, $exclude)) {
                $a[$k] = htmlspecialchars((string)$v ?? '');
            }
        }
        return;
    }
}
