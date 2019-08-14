<?php
/**
 *
 * @author  wangg <alonegrowing@gmail.com>
 * @since   2018-11-06
 * @version v0.1.0
 */
namespace MicroFrame\DB\Access;


use Medoo\Medoo;

class MinDb {

    public $MinDb = null;


    /**
     * @var $_instance MinDb
     */
    public  static $_instance = null;

    function __construct($dbConfig)
    {

        if (empty($dbConfig)) {
            return;
        }
        $this->MinDb = new Medoo([
            //[required]
            'database_type' => 'mysql',
            'database_name' => $dbConfig['db'],
            'server' => $dbConfig['host'],
            'username' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'port' => $dbConfig['port'],

            // [optional]
            'charset' => 'utf8',
            'prefix' => '',
            'logging' => true,
        ]);


        $this->expireTime = time() + $dbConfig["pool"]["timeout"];  //过期时间
    }

    public static function getInstance($dbConfig) {
        if (static::$_instance === null ||  time() > self::$_instance->expireTime || !(static::$_instance instanceof self)) {
            static::$_instance = new self($dbConfig);
        }

        return static::$_instance;
    }
}