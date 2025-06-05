<?php

declare(strict_types=1);

namespace App;

use App\Utility;
use App\Database\DBCore;

class Route
{
    public static function parseRoute($queryString)
    {
        parse_str($queryString, $q);
        if (isset($q['list'])) {
            $hash = ($q['list'] == 'alltasks') ? ['alltasks'] : ['list', (int)$q['list']];
            unset($q['list']);
            self::redirectWithHashRoute($hash, $q);
        } else if (isset($q['task'])) {
            $listId = (int)DBCore::default()->getListIdByTaskId((int)$q['task']);
            if ($listId > 0) {
                $h = [ 'list', $listId, 'search', '#' . (int)$q['task']];
                self::redirectWithHashRoute($h);
            }
            // TODO: not found
        }
    }

    private static function redirectWithHashRoute(array $hash, array $q = [])
    {
        $url = Utility::getUnsafeMttinfo('url');
        $query = http_build_query($q);

        if ($query != '') {
            $url .= "?$query";
        }

        if (count($hash) > 0) {
            $encodedHash = implode("/", array_map("rawurlencode", $hash));
            $url .= "#$encodedHash";
        }
        header("Location: " . $url);
        exit;
    }
}
