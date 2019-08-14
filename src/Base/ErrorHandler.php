<?php
namespace MicroFrame\Base;

use Ap;

/**
 * 处理未被捕获的PHP错误或者异常
 *
 * @author alonegrowing <alonegrowing@gmail.com>
 * @since v0.1.0
 */
class ErrorHandler 
{	
    private static $_instance = NULL;
    
    private function __construct(){}
    
    /**
     * 返回ErrorHandler的单例
     * @return ErrorHandler
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
	/**
	 * 注册错误处理Handler
	 */
	public function register()
	{
		ini_set('display_errors', false);
		set_exception_handler([$this, 'handleException']);
		set_error_handler([$this, 'handleError']);
		register_shutdown_function([$this, 'handleFatalError']);
	}
	
	/**
	 * 通过恢复注销之前注册的错误处理器
	 */
	public function unregister()
	{
		restore_error_handler();
		restore_exception_handler();
	}
	
	/**
	 * 处理未捕获的PHP异常
	 *
	 * @param \Exception $exception 
	 */
	public function handleException($exception)
	{	
		
		//防止递归异常处理
		$this->unregister();

		$this->renderException($exception);
	}
	
	/**
	 * 处理PHP执行过程中遇到 WARNING 和 NOTICE 错误.
	 * @param int $code 错误的级别编码
	 * @param string $message 错误信息
	 * @param string $file 出现错误的文件名
	 * @param int $line 出现错误的行信息
	 * @return boolean
	 */
	public function handleError($code, $message, $file, $line)
	{
	    $msg = "{$this->getName($code)}: {$message} in {$file} on line {$line}";
	    $this->renderError($msg);
	}
	
	/**
	 * 处理致命错误
	 */
	public function handleFatalError()
	{
		$error = error_get_last();
		if ( self::isFatalError($error) ) {
			$code = $error['type']; 
			$message = $error['message'];
			$file = $error['file'];
			$line = $error['line'];
			
			$msg = "{$this->getName($code)}: {$message} in {$file} on line {$line}";
			$this->renderError($msg);
		} 
	}
	
	/**
	 * 判断当前的错误是否是FatalError错误
	 * 
	 * @param array $error 
	 * @return bool 
	 */
	public static function isFatalError($error)
	{
		return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
	}
	
	/**
	 * 输出异常，同时记录异常详细信息到日志文件中
	 * 
	 * @param \Exception $exception 
	 */
	public  function renderException($exception) {	    	    
	    $msg = "{$this->getName($exception->getCode())}: {$exception->getMessage()} in {$file} on line {$line}";	    
	    
	    Ap::error($msg);		
	}
	
	/**
	 * 输出错误, 同时记录错误到日志文件
	 */
	public function renderError($message) {	    
	    Ap::error($message);   
	}
	
	/**
	 * PHP错误级别，对应的错误原因
	 * @param int $type
	 * @return string
	 */
	public function getName($type)
	{
		static $names = [
			E_COMPILE_ERROR 	=> 	'PHP Compile Error',
			E_COMPILE_WARNING 	=> 	'PHP Compile Warning',
			E_CORE_ERROR 		=> 	'PHP Core Error',
			E_CORE_WARNING 		=> 	'PHP Core Warning',
			E_DEPRECATED 		=> 	'PHP Deprecated Warning',
			E_ERROR 			=> 	'PHP Fatal Error',
			E_NOTICE 			=> 	'PHP Notice',
			E_PARSE 			=> 	'PHP Parse Error',
			E_RECOVERABLE_ERROR => 	'PHP Recoverable Error',
			E_STRICT 			=> 	'PHP Strict Warning',
			E_USER_DEPRECATED 	=> 	'PHP User Deprecated Warning',
			E_USER_ERROR 		=> 	'PHP User Error',
			E_USER_NOTICE 		=> 	'PHP User Notice',
			E_USER_WARNING 		=> 	'PHP User Warning',
			E_WARNING 			=> 	'PHP Warning',
		];		
		return isset($names[$type]) ? $names[$type] : 'Error';
	}
}
