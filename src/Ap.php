<?php
use MicroFrame\Log\Logger;

use MicroFrame\DB\Access\OrmBootStrap;

use MicroFrame\DB\Access\MinDb;

/**
 * Ap is a helper class serving common framework functionalities.
 *
 * @author alonegrowing <alonegrowing@gmail.com>
 * @version v0.1.0
 */
class Ap 
{
	/**
	 * 当前Http服务实例
	 *
     * @var MicroFrame\Http\Application
     */
	public static $app;
	
	/**
	 * 配置文件实例
	 *
	 * @var  $config \Noodlehaus\Config
	 */
	public static $config;

    /**
     * @var $Entity OrmBootStrap
     */
	public static $Entity;

    /**
     * @var $MinDbEntity MinDb
     */
	public static $MinDbEntity;

	/**
	 * 自动加载部分类
	 * 
	 * todo 提升加载效率，需要spl_autoload_register()注册到加载函数栈中
	 * 
	 * @param string $className
	 */
	public static function autoload($className) {
		if (strpos($className, '\\') !== false) {
			$path = str_replace('\\', '/', $className);
			
			$params = explode('/', $path);
			$params[0] = strtolower($params[0]);
			
			$classFile = ROOT_PATH . implode('/', $params) . '.php';

			if ($classFile === false || !is_file($classFile)) {
				return;
			}
		} else {
			return;
		}
		//echo ($classFile) . "\n";
		include $classFile;
	}

    /**
     * Debug日志，记录程序执行的信息
     *
     * @param string $msg
     * @param array $params
     */
	public static function debug($msg, $params = []) {
	    Logger::log(Logger::LEVEL_DEBUG, $msg, 0, $params);
	}

    /**
     * Trace日志，记录程序执行的信息
     *
     * @param string $msg
     * @param array $params
     */
	public static function trace($msg, $params = []) {
	    Logger::log(Logger::LEVEL_TRACE, $msg, 0, $params);
	}

    /**
     * Info日志，记录程序执行的信息
     *
     * @param string $msg
     * @param array $params
     */
	public static function info($msg, $params = []) {
	    Logger::log(Logger::LEVEL_INFO, $msg, 0, $params);
	}

    /**
     * Warning日志，记录程序的警告错误
     *
     * @param string $msg
     * @param array $params
     */
	public static function warning($msg, $params = []) {    
	   Logger::log(Logger::LEVEL_WARNING, $msg, 0, $params);
	}

    /**
     * Error日志，记录程序的严重错误
     *
     * @param string $msg
     * @param int $errno
     * @param array $params
     */
	public static function error($msg, $errno = 1, $params = []) {  
	    Logger::log(Logger::LEVEL_FATAL, $msg, $errno, $params);
	}
	
	/**
	 * Pid写入文件
	 */
	public static function writePid() {
		$pid = posix_getpid();
		
		$path = ROOT_PATH . '/var';
				
		Logger::recursiveMkdir($path);
		
		$filename = $path . '/pid';
		
		file_put_contents($filename, $pid);
	}

	public static function getPid() {
        $path = ROOT_PATH . '/var';
        $filename = $path . '/pid';
        $pid = file_get_contents($filename);
        if (false === $pid) {
            return "";
        } elseif ($pid === '') {
            return "";
        }
        return $pid;
    }

    /**
     * @param $dbconfig
     *
     */
    public static function OrmStart($dbconfig) {
        self::$Entity = OrmBootStrap::getInstance($dbconfig);
    }

    public static function MinDbStart($dbconfig) {
        self::$MinDbEntity = MinDb::getInstance($dbconfig);
    }
}
spl_autoload_register(['Ap', 'autoload'], true, true);