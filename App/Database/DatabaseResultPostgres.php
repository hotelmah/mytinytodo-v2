<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Database;

use PDO;

// ---------------------------------------------------------------------------- //
class DatabaseResultPostgres extends DatabaseResultAbstract
{
    /** @var PDOStatement */
    protected $q;

    /** @var int */
    protected $affected;

    public function __construct(PDO $dbh, string $query, bool $resultless = false)
    {
        // use with DELETE, INSERT, UPDATE
        if ($resultless) {
            $this->affected = (int) $dbh->exec($query); //throws PDOException
        } else {
            // SELECT
            $this->q = $dbh->query($query); //throws PDOException
            $this->affected = $this->q->rowCount();
        }
    }

    public function fetchRow(): ?array
    {
        $res = $this->q->fetch(PDO::FETCH_NUM);
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    public function fetchAssoc(): ?array
    {
        $res = $this->q->fetch(PDO::FETCH_ASSOC);
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    public function rowsAffected(): int
    {
        return $this->affected;
    }
}
