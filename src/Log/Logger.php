<?php
namespace MicroFrame\Log;

use Ap;
use MicroFrame\Server\Http;
/**
 * 日志处理基类 
 * 
 * @author Gang Wang <alonegrowing@gmail.com>
 * @author Levin <alonegrowing@gmail.com>
 * @date 28/10/2018
 * @version v0.1.0     
 */
class Logger
{
    const LEVEL_FATAL = 0x10;
    const LEVEL_WARNING = 0x08;
    const LEVEL_INFO = 0x04;
    const LEVEL_TRACE = 0x02;
    const LEVEL_DEBUG = 0x01;
    
    const DEFAULT_FORMAT = '%L: %t [%f:%N] errno[%E] time[%T] params[%S] %M';
    
    public $messages = [];
    
    public $flushInterval = 1000;
    
    public $traceLevel = 0;
 
    /**
     * 日志格式化，输出到文件
     * todo 输出到不同的端
     * 
     * @param int $level
     * @param string $msg
     * @param int $errno
     * @param array $params
     * @param int $depth
     * @return string 返回格式化的日志
     */
    public static function log($level, $msg, $errno, $params = null, $depth = 0)
    {   
        //日志级别过滤
        if ($level < Ap::$config["log.level"]) {
            return;
        }
        
        $trace = debug_backtrace();
        $depth = $depth + 1;
        if ($depth >= count($trace))
        {
            $depth = count($trace) - 1;
        }
        
        $file = isset($trace[$depth]['file']) ? $trace[$depth]['file'] : "";
        $line = isset($trace[$depth]['line']) ? $trace[$depth]['line'] : "";
        
        // get the format
        $format = self::DEFAULT_FORMAT;
        
        $matches = array ();
        $regex = '/%(?:{([^}]*)})?(.)/';
        preg_match_all($regex, $format, $matches);
        
        $prelim = array ();
        $action = array ();
        $prelim_done = array ();
        $len = count($matches[0]);
        for ($i = 0; $i < $len; $i++)
        {
            $code = $matches[2][$i];
            $param = $matches[1][$i];
            switch ($code)
            {
                case 'L':
                    $action[] = self::getLevelName($level);
                    break;
                case 't':
                    $action[] = date('Y-m-d H:i:s');
                    break;
                case 'f':
                    $action[] = $file;
                    break;
                case 'N':
                    $action[] = $line;
                    break;
                case 'E':
                    $action[] = $errno;
                    break;
                case 'S':
                    $action[] = json_encode($params);
                    break;
                case 'T':
                    $action[] = self::getElapsedTime();
                    break;
                case 'M':
                    $action[] = $msg;
                    break;
                case '%':
                    $action[] = "'%'";
                    break;
                case 'm':
                    $action[] = "method";//$method;
                    break;
                default:
                    $action[] = "''";
            }
        }
        $strformat = preg_split($regex, $format);
        $code = var_export($strformat[0], true);
        for ($i = 1; $i < count($strformat); $i++)
        {
            $code .= trim($action[$i - 1], "'") . trim(var_export($strformat[$i], true), "'");
        }
        
        $code .= PHP_EOL;
        $code = trim($code, "'");
        
        //获取日志的文件地址 | 发布服务需要指定日志目录(线上/a8root/**), SO 日志目录需要可配置 |
        $logPath = Ap::$config['log.path'];
        if (!is_dir($logPath)) {
        	self::recursiveMkdir($logPath);
        }
        $strLogFile = $logPath . strtolower(self::getLevelName($level)) . ".log." . date('YmdH');
        
        if ($level > self::LEVEL_TRACE) {
            go(function () use ($strLogFile, $code) {
                file_put_contents($strLogFile, $code, FILE_APPEND);
            });
        } else {
            file_put_contents($strLogFile, $code, FILE_APPEND);
        } 
    }
    
    /**
     * 递归创建目录
     * @param string $pathname 需要创建的目录路径
     * @param int $mode 创建的目录属性，默认为755
     * @return void
     */
    public static function recursiveMkdir($pathname, $mode = 0755) {
    	return is_dir($pathname) ? true : mkdir($pathname, $mode, true);
    }
    
    /**
     * Returns the total elapsed time since the start of the current request.
     * This method calculates the difference between now and the timestamp
     * @return float the total elapsed time in seconds for current request.
     */
    public static function getElapsedTime()
    {
        return round((microtime(true) - Http::getInstance()->getApBeginTime()) * 1000,  2) . 'ms';
    }

    /**
     * 根据日志级别获取日志名称
     * @param int $level
     * @return string
     */
    public static function getLevelName($level)
    {
        static $levels = [
            self::LEVEL_FATAL   => 'ERROR',
            self::LEVEL_WARNING => 'WARNING',
            self::LEVEL_INFO    => 'INFO',
            self::LEVEL_TRACE   => 'TRACE',
            self::LEVEL_DEBUG   => 'DEBUG',          
        ];
        
        return isset($levels[$level]) ? $levels[$level] : 'unknown';
    }
}
