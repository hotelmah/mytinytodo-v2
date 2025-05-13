<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Config;

use Exception;
use App\Utility;
use App\Utility2;
use App\Database\DBConnection;
use App\Database\DBCore;
use App\Database\DatabaseSqlite3;
use App\Database\DatabaseMysqli;
use App\Database\DatabaseMysql;
use App\Database\DatabasePostgres;

class Config
{
    /** @var bool */
    public static $noDatabase = false;

    /** @var array[] */
    private static $dbparams = array(
        # Database type: sqlite or mysql
        'db.type'      => array('default' => 'sqlite', 'type' => 's'),

        # Specific database api
        'db.driver'    => array('default' => '', 'type' => 's'),

        # Mysql connection settings
        'db.host'     => array('default' => 'localhost',  'type' => 's'),
        'db.user'     => array('default' => 'mtt',        'type' => 's'),
        'db.password' => array('default' => 'mtt',        'type' => 's'),
        'db.name'     => array('default' => 'mytinytodo', 'type' => 's'),

        # Prefix for table names
        'db.prefix'   => array('default' => '', 'type' => 's')
    );

    /** @var array[] */
    private static $convert = array(
        'mysql.host' => 'db.host',
        'mysql.user' => 'db.user',
        'mysql.password' => 'db.password',
        'mysql.db' => 'db.name',
        'db' => 'db.type',
        'prefix' => 'db.prefix'
    );

    /** @var array[] */
    public static $params = array(
        # These two parameters are used when mytinytodo index.php called not from installation directory
        # 'url' - URL where index.php is called from (ex.: http://site.com/todo.php)
        # 'mtt_url' - directory URL where mytinytodo is installed (with trailing slash) (ex.: http://site.com/lib/mytinytodo/)
        'url' => array('default' => '', 'type' => 's'),
        'mtt_url' => array('default' => '', 'type' => 's'),

        # Top title
        'title' => array('default' => '', 'type' => 's'),

        # Language pack
        'lang' => array('default' => 'en', 'type' => 's'),

        # Password to protect your tasks from modification,
        # leave empty that everyone could read/write todolist
        'password' => array('default' => '', 'type' => 's'),

        # Smart Syntax enabled flag
        'smartsyntax' => array('default' => 1, 'type' => 'i'),

        # Default Time zone
        'timezone' => array('default' => 'UTC', 'type' => 's'),

        # To disable auto adding selected tag set value to 0
        'autotag' => array('default' => 1, 'type' => 'i'),

        # duedate calendar format: 1 => y-m-d (default), 2 => m/d/y, 3 => d.m.y
        'duedateformat' => array('default' => 1, 'type' => 'i'),

        # First day of week: 0-Sunday, 1-Monday, 2-Tuesday, .. 6-Saturday
        'firstdayofweek' => array('default' => 1, 'type' => 'i', 'options' => array(0,1,2,3,4,5,6)),

        # Date/time formats
        'clock' => array('default' => 24, 'type' => 'i', 'options' => array(12,24)),
        'dateformat' => array('default' => 'j M Y', 'type' => 's'),
        'dateformat2' => array('default' => 'n/j/y', 'type' => 's'),
        'dateformatshort' => array('default' => 'j M', 'type' => 's'),

        # Show task date in list
        'showdate' => array('default' => 0, 'type' => 'i'),
        'showtime' => array('default' => 0, 'type' => 'i'),
        'showdateInline' => array('default' => 0, 'type' => 'i'),
        'exactduedate' => array('default' => 0, 'type' => 'i'),

        # Use Markdown syntax for notes. Set to 'v1' to use old v1.6 syntax.
        'markup' => array('default' => 'markdown', 'type' => 's'),

        # Appearance: system default or always light
        'appearance' => array('default' => 'system', 'type' => 's', 'options' => array('system','light','dark')),

        # New tasks counter
        'newTaskCounter' => array('default' => 0, 'type' => 'i'),

        # Array of activated extensions
        'extensions' => array('default' => [], 'type' => 'a')
    );

    /** @var mixed[] */
    private static $config = array();

    /* ===================================================================================================================== */

    /**
     *
     * @param mixed[] $config
     * @return void
     */
    public static function loadConfigV14(array $config)
    {
        foreach ($config as $key => $val) {
            if (isset(self::$convert[$key])) {
                $key = self::$convert[$key];
            } elseif ($key == 'mysqli' && (int)$val != 0) {
                $key = 'db.driver';
                $val = 'mysqli';
            } elseif ($key == 'password' && $val != '') {
                $val = Utility2::passwordHash($val); // in v1.7 password is hashed
            }

            if (!isset(self::$dbparams[$key])) {
                throw new Exception("Unknown key: $key");
            }

            self::$config[$key] = $val;
        }
    }

    /**
     *
     * @return void
     * @throws Exception
     */
    public static function load()
    {
        if (self::$noDatabase) {
            return;
        }

        $j = self::requestDefaultDomain();

        foreach ($j as $key => $val) {
            // Ignore params for database config
            if (!isset(self::$dbparams[$key])) {
                self::$config[$key] = $val;
            }
        }
    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        } elseif (isset(self::$params[$key])) {
            return self::$params[$key]['default'];
        } elseif (isset(self::$dbparams[$key])) {
            return self::$dbparams[$key]['default'];
        } else {
            return null;
        }
    }

    /**
     *
     * @param string $key
     * @return string|null
     */
    public static function getUrl($key)
    {
        $url = '';

        if (isset(self::$config[$key])) {
            // $url = self::$config[$key];
            $url = '/mytinytodo/api';
        } elseif (isset(self::$params[$key])) {
            $url = self::$params[$key]['default'];
        } else {
            return null;
        }

        return str_replace(["\r","\n"], '', $url);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public static function set($key, $value)
    {
        if ($key == "db.prefix" && $value != "" && !preg_match("/^[a-zA-Z0-9_]+$/", $value)) {
            throw new Exception("Incorrect table prefix. Can contain only latin letters, digits and underscore character.");
        }

        self::$config[$key] = $value;
    }


    /**
     *
     * @return void
     * @throws Exception
     */
    public static function save()
    {
        $j = array();
        foreach (self::$params as $param => $v) {
            if (!isset(self::$config[$param])) {
                $val = $v['default'];
            } elseif (isset($v['options']) && !in_array(self::$config[$param], $v['options'])) {
                $val = $v['default'];
            } else {
                $val = self::$config[$param];
            }

            if ($v['type'] == 'i') {
                $val = (int)$val;
            } elseif ($v['type'] == 'a') {
                if (!is_array($val)) {
                    $val = [];
                }
            } else {
                $val = strval($val);
            }

            $j[$param] = $val;
        }

        self::saveDomain('config.json', $j);
    }

    /**
     *
     * @param string $key
     * @return array
     * @throws Exception
     */
    public static function requestDomain(string $key): array
    {
        $db = DBConnection::instance();
        $json = $db->sq("SELECT param_value FROM {$db->prefix}settings WHERE param_key = ?", array($key));
        if (!$json) {
            return array();
        }
        $j = json_decode($json, true, 100, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($j === null) {
            error_log("MTT Error: Failed to decode JSON object with settings. Code: " . (int)json_last_error());
            return array();
        }
        return $j;
    }


    /**
     *
     * @return array
     * @throws Exception
     */
    public static function requestDefaultDomain(): array
    {
        return self::requestDomain('config.json');
    }


    /**
     *
     * @param string $key
     * @param array $array
     * @return void
     * @throws Exception
     */
    public static function saveDomain($key, $array)
    {
        $json = json_encode($array, JSON_PRETTY_PRINT /*| JSON_INVALID_UTF8_SUBSTITUTE*/);
        if ($json === false) {
            throw new Exception("Failed to create JSON object with settings. Code: " . (int)json_last_error());
        }
        $db = DBConnection::instance();
        $keyExists = $db->sq("SELECT COUNT(param_key) FROM {$db->prefix}settings WHERE param_key = ?", array($key));
        if ($keyExists) {
            $db->ex("UPDATE {$db->prefix}settings SET param_value = ? WHERE param_key = ?", array($json,$key));
        } else {
            $db->ex("INSERT INTO {$db->prefix}settings (param_key,param_value) VALUES (?,?)", array($key,$json));
        }
    }

    public static function defineDbConstants()
    {
        define("MTT_DB_TYPE", self::get('db.type'));
        define("MTT_DB_HOST", self::get('db.host'));
        define("MTT_DB_USER", self::get('db.user'));
        define("MTT_DB_PASSWORD", self::get('db.password'));
        define("MTT_DB_NAME", self::get('db.name'));
        define("MTT_DB_PREFIX", self::get('db.prefix'));
        if (self::get('db.driver') != '') {
            define("MTT_DB_DRIVER", self::get('db.driver'));
        }
    }

    public static function dbConfigAsFileContents(): string
    {
        $a = array();
        $a[] = "<?php\n";
        $a[] = "// myTinyTodo Database connection configuration\n";
        $a[] = self::prepareDbDefine("MTT_DB_TYPE", self::get('db.type')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_HOST", self::get('db.host')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_USER", self::get('db.user')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_PASSWORD", self::get('db.password')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_NAME", self::get('db.name')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_PREFIX", self::get('db.prefix')) . "\n";
        $a[] = self::prepareDbDefine("MTT_DB_DRIVER", self::get('db.driver')) . "\n";
        $a[] = self::prepareDbDefine("MTT_SALT", defined('MTT_SALT') ? MTT_SALT : Utility2::generateUUID()) . "\n";
        return implode("\n", $a);
    }

    private static function prepareDbDefine(string $key, string $value): string
    {
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $key)) {
            throw new Exception("Unexpected constant name: " . $key);
        }
        if (preg_match('~\R~', $value)) { # newlines
            throw new Exception("Unexpected constant value: " . $value);
        }
        return "define('$key', '" . str_replace(array("\\",   "'" ), array("\\\\", "\\'"), $value) . "');";
    }

    public static function saveDbConfig()
    {
        $contents = self::dbConfigAsFileContents();
        $f = fopen(MTTPATH . 'config.php', 'w');
        if ($f === false) {
            throw new Exception("Error while saving config file");
        }

        fwrite($f, $contents);
        fclose($f);

        //Reset Zend OPcache
        //opcache_get_status() sometimes crashes
        if (function_exists("opcache_invalidate") && 0 != (int)opcache_get_configuration()["directives"]["opcache.enable"]) {
            opcache_invalidate(MTTPATH . 'config.php', true);
        }
    }

    public static function loadDatabaseConfig()
    {
        $exists = file_exists('../App/Config/config.database.php');
        $defined = false;
        if ($exists) {
            require_once('../App/Config/config.database.php');
            $defined = defined('MTT_DB_TYPE');
        }
        # It seems not installed
        if (!$defined) {
            die("Not installed. Run <a href=setup.php>setup.php</a> first.");
        }
    }

    public static function configureDbConnection()
    {
        # MySQL Database Connection
        if (MTT_DB_TYPE == 'mysql') {
            if (defined('MTT_DB_DRIVER') && MTT_DB_DRIVER == 'mysqli') {
                $db = new DatabaseMysqli();
            } else {
                $db = new DatabaseMysql();
            }
            DBConnection::init($db);
            try {
                $db->connect([
                    'host' => MTT_DB_HOST,
                    'user' => MTT_DB_USER,
                    'password' => MTT_DB_PASSWORD,
                    'db' => MTT_DB_NAME,
                ]);
            } catch (Exception $e) {
                Utility::logAndDie("Failed to connect to mysql database: " . $e->getMessage());
            }
            $db->dq("SET NAMES utf8mb4");
        } else if (MTT_DB_TYPE == 'postgres') {
            # PostgreSQL Database
            $db = DBConnection::init(new DatabasePostgres());
            try {
                $db->connect([
                    'host' => MTT_DB_HOST,
                    'user' => MTT_DB_USER,
                    'password' => MTT_DB_PASSWORD,
                    'db' => MTT_DB_NAME,
                ]);
            } catch (Exception $e) {
                $errlog = "Failed to connect to PostgreSQL database: " . $e->getMessage();
                if (MTT_DEBUG) {
                    Utility::logAndDie($errlog);
                } else {
                    Utility::logAndDie("Failed to connect to database", $errlog);
                }
            }
            $db->dq("SET NAMES 'utf8'");
        } elseif (MTT_DB_TYPE == 'sqlite') {
            # SQLite3 Database
            $db = DBConnection::init(new DatabaseSqlite3([]));
            $db->connect([
                'filename' => '../todolist.db'
            ]);
        } else {
            die("Incorrect database connection config");
        }

        DBConnection::setTablePrefix(MTT_DB_PREFIX);
        DBCore::setDefaultInstance(new DBCore($db));

        # Check tables created
        global $checkDbExists;
        if (!Config::$noDatabase && isset($checkDbExists) && $checkDbExists) {
            $exists = $db->tableExists($db->prefix . 'settings');
            if (!$exists) {
                die("Need to create or update the database. Run <a href=setup.php>setup.php</a> first.");
            }
        }
    }
}
