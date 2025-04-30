<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use Exception;

class DatabasePostgres extends DatabaseAbstract
{
    const DBTYPE = 'postgres';

    /** @var PDO */
    protected $dbh;

    /** @var int */
    protected $affected = 0;

    protected $dbname;

    /** @var string  const */
    protected $schema = 'public';

    public function __construct()
    {
        // ...
    }

    public function connect(array $params): void
    {
        $host = $params['host'];
        $user = $params['user'];
        $pass = $params['password'];
        $db = $params['db'];
        $options = array(
            PDO::PGSQL_ATTR_DISABLE_PREPARES => 1,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        $this->dbname = $db;
        $this->dbh = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, $options);
    }



    /*
        Returns single row of SELECT query as indexed array (FETCH_NUM).
        Returns single field value if resulting array has only one field.
    */
    public function sq(string $query, ?array $values = null)
    {
        $q = $this->dQuery($query, $values);

        $res = $q->fetchRow();
        if ($res === false || !is_array($res)) {
            return null;
        }

        if (sizeof($res) > 1) {
            return $res;
        } else {
            return $res[0];
        }
    }

    /*
        Returns single row of SELECT query as dictionary array (FETCH_ASSOC).
    */
    public function sqa(string $query, ?array $values = null): ?array
    {
        $q = $this->dQuery($query, $values);
        $res = $q->fetchAssoc();
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    public function dq(string $query, ?array $values = null): DatabaseResultAbstract
    {
        return $this->dQuery($query, $values);
    }

    /*
        for resultless queries like INSERT,UPDATE,DELETE
    */
    public function ex(string $query, ?array $values = null): void
    {
        $this->dQuery($query, $values, true);
    }

    private function dQuery(string $query, ?array $values = null, bool $resultless = false): DatabaseResultAbstract
    {
        if (null !== $values && sizeof($values) > 0) {
            $m = explode('?', $query);
            if (sizeof($m) < sizeof($values) + 1) {
                throw new Exception("params to set MORE than query params");
            }
            if (sizeof($m) > sizeof($values) + 1) {
                throw new Exception("params to set LESS than query params");
            }
            $query = "";
            for ($i = 0; $i < sizeof($m) - 1; $i++) {
                $query .= $m[$i] . $this->quote($values[$i]);
            }
            $query .= $m[$i];
        }
        $this->lastQuery = $query;
        $dbr = new DatabaseResultPostgres($this->dbh, $query, $resultless);
        $this->affected = $dbr->rowsAffected();
        return $dbr;
    }

    public function affected(): int
    {
        return $this->affected;
    }

    public function quote($value): string
    {
        if (null === $value) {
            return 'null';
        }
        return $this->dbh->quote((string) $value);
    }

    public function quoteForLike(string $format, string $string): string
    {
        $string = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $string);
        return $this->dbh->quote(sprintf($format, $string)) . " ESCAPE '\'";
    }

    public function like(string $column, string $format, string $string): string
    {
        $column = str_replace('"', '""', $column);
        return '"' . $column . '" ILIKE ' . $this->quoteForLike($format, $string);
    }

    public function ciEquals(string $column, string $value): string
    {
        $column = str_replace('"', '""', $column);
        return 'LOWER("' . $column . '") = LOWER(' . $this->quote($value) . ')';
    }

    public function lastInsertId(?string $name = null): ?string
    {
        $ret = $this->dbh->lastInsertId();
        if (false === $ret) {
            return null;
        }
        return (string) $ret;
    }

    public function tableExists(string $table): bool
    {
        $r = $this->sq("SELECT 1 FROM information_schema.tables WHERE table_catalog = ? AND table_name = ?", array($this->dbname, $table));
        if ($r === false || $r === null) {
            return false;
        }
        return true;
    }

    public function tableFieldExists(string $table, string $field): bool
    {
        $r = $this->sq("SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ? AND table_schema = ?", array($table, $field, $this->schema));
        if ($r === false || $r === null) {
            return false;
        }
        return true;
    }
}
