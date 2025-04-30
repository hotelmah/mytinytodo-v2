<?php

declare(strict_types=1);

namespace App\Database;

abstract class DatabaseResultAbstract
{
    abstract public function fetchRow(): ?array;
    abstract public function fetchAssoc(): ?array;
}
