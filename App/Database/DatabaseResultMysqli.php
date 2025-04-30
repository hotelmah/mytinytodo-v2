<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2019-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Database;

use mysqli;

class DatabaseResultMysqli extends DatabaseResultAbstract
{
    /** @var mysqli_result */
    protected $q;

    public function __construct(mysqli $dbh, string $query, bool $resultless = false)
    {
        $this->q = $dbh->query($query); //throws mysqli_sql_exception
    }

    public function fetchRow(): ?array
    {
        $res = $this->q->fetch_row();
        if ($res === null || $res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    public function fetchAssoc(): ?array
    {
        $res = $this->q->fetch_assoc();
        if ($res === null || $res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }
}
