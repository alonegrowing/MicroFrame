<?php
/**
 *
 * @author  wangg <alonegrowing@gmail.com>
 * @since   2018-11-05
 * @version v0.1.0
 */

namespace MicroFrame\DB\Access;


use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class OrmBootStrap {

    public $entityManager;

    public $isDevMode   = true;

    public $strapConfig;

    public  $expireTime = 0 ;


    /**
     * @var $_instance OrmBootStrap
     */
    public  static $_instance = null;

    function __construct($dbConfig)
    {
        if (empty($dbConfig)) {
            return;
        }

        $conn = array(
            'driver' => 'pdo_mysql',
            'host' => $dbConfig['host'],
            'port' => $dbConfig['port'],
            'user' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'dbname' => $dbConfig['db'],
        );


        $this->expireTime = time() + $dbConfig["pool"]["timeout"];  //过期时间


        $modelConfig =  defined ( 'ORM_DIR' ) ? ORM_DIR : defined("ORMODEL_DIR") ? ORMODEL_DIR  : "" ;
        $this->strapConfig = Setup::createAnnotationMetadataConfiguration(array($modelConfig), $this->isDevMode);
        $this->entityManager = EntityManager::create($conn, $this->strapConfig);
    }



    public static function getInstance($dbConfig) {
        if (static::$_instance === null ||  time() > self::$_instance->expireTime || !(static::$_instance instanceof self)) {
            static::$_instance = new self($dbConfig);
        }

        return static::$_instance;
    }
}