<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Utility;

use Exception;

class Security
{
    /**
    * Generates UUID v4
    * Implementation from https://github.com/symfony/polyfill-uuid
    */
    public static function generateUUID(): string
    {
        $uuid = bin2hex(random_bytes(16));
        return sprintf(
            '%08s-%04s-4%03s-%04x-%012s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            // $uuid[14] = 4
            substr($uuid, 13, 3),
            hexdec(substr($uuid, 16, 4)) & 0x3fff | 0x8000,
            substr($uuid, 20, 12)
        );
    }

    public static function passwordHash(string $p): string
    {
        if ($p == '') {
            return '';
        }
        return 'sha256:' . hash('sha256', $p);
    }

    /**
    * Compares raw (not hashed) password with password hash. Return true if equals.
    * @param string $p Raw password
    * @param string $hash Password hash
    * @return bool
    */
    public static function isPasswordEqualsToHash(string $p, string $hash): bool
    {
        if ($hash == '' && $p == '') {
            return true;
        }
        if ($hash == '' || $p == '') {
            return false;
        }
        if (false !== $pos = strpos($hash, ':')) {
            $algo = substr($hash, 0, $pos);
            if ($algo != 'sha256') {
                throw new Exception("Unsupported algo of password hash");
            }
            if (hash_equals($hash, self::passwordHash($p))) {
                return true;
            }
        }
        return false;
    }

    public static function idSignature(string $id, string $key, string $salt): string
    {
        $secret = $key . $salt;
        return hash_hmac('sha256', $id, $secret);
    }

    public static function isValidSignature(string $signature, string $id, string $key, string $salt): bool
    {
        if (hash_equals($signature, self::idSignature($id, $key, $salt))) {
            return true;
        }
        return false;
    }

    public static function randomString(int $len = 16, string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $a = [];
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $a[] = $chars[random_int(0, $max)];
        }
        return implode('', $a);
    }
}
