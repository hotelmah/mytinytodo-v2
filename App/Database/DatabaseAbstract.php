<?php

declare(strict_types=1);

namespace App\Database;

use Error;
use Exception;

abstract class DatabaseAbstract
{
    const DBTYPE = '';
    protected static $readonlyProps = ['prefix', 'lastQuery'];

    /** @var string */
    protected $prefix = '';

    /** @var string */
    protected $lastQuery = '';

    abstract public function connect(array $params): void;
    abstract public function sq(string $query, ?array $values = null);
    abstract public function sqa(string $query, ?array $values = null): ?array;
    abstract public function dq(string $query, ?array $values = null): DatabaseResultAbstract;
    abstract public function ex(string $query, ?array $values = null): void;
    abstract public function affected(): int;
    abstract public function quote($value): string;
    abstract public function quoteForLike(string $format, string $string): string;
    abstract public function like(string $column, string $format, string $string): string;
    abstract public function ciEquals(string $column, string $value): string;
    abstract public function lastInsertId(?string $name = null): ?string;
    abstract public function tableExists(string $table): bool;
    abstract public function tableFieldExists(string $table, string $field): bool;

    public function __get(string $propName)
    {
        if (in_array($propName, self::$readonlyProps)) {
            return $this->{$propName};
        }
        throw new Error("Attempt to read undefined property " . get_class($this) . "::\$$propName");
    }

    public function setPrefix(string $prefix): void
    {
        if ($prefix != '' && !preg_match("/^[a-zA-Z0-9_]+$/", $prefix)) {
            throw new Exception("Incorrect table prefix");
        }
        $this->prefix = $prefix;
    }
}
