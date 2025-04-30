<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021,2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Database;

use Exception;

class DBConnection
{
    const DBTYPE_SQLITE = "sqlite";
    const DBTYPE_MYSQL = "mysql";
    const DBTYPE_POSTGRES = "postgres";

    protected static $instance;

    public static function init(DatabaseAbstract $instance): DatabaseAbstract
    {
        self::$instance = $instance;
        return $instance;
    }

    public static function instance(): DatabaseAbstract
    {
        if (!isset(self::$instance)) {
            throw new Exception("DBConnection is not initialized");
        }
        return self::$instance;
    }

    public static function setTablePrefix($prefix)
    {
        $db = self::instance();
        $db->setPrefix($prefix);
    }
}
