<?php

namespace MicroFrame\Routing;

use Ap;
use MicroFrame\Http\Application;

/**
 * 微服务框架的路由，当前支持默认路由 
 * 
 * todo 支持路由映射，可以自定义路由规则，匹配指定的控制器方法
 * 
 * @author alonegrowing <alonegrowing@gmail.com>
 *
 */
class Router 
{
	
	const CONTROLLER = "controller";
	
	const ACTION = "action";
	
	const URIPARTS_COUNT = 4;
	
	protected static $route;
	
	private static $defaultUri = "api/welcome/default/index";
	
	private static $_instance = NULL;
	
	private function __construct(){}
	private function __clone(){}
	private function __destruct(){}
	
	/**
	 * 返回Router的单例
	 * @return \Ap\MicroFrame\Http\Router
	 */
	public static function getInstance() {
	    if (is_null(self::$_instance)) {
	        self::$_instance = new self();
	    }
	    return self::$_instance;
	}
	
	/**
	 * 路由配置初始化
	 */
	public function parse() 
	{
	    $uri = $this->resolveRequestUri();
	    
		return $this->getRouteParts($uri);
	}
	
	/**
	 * 路由核心函数，当前只支持按默认的api/模块/控制器/方法的模式（api/module/controllerPrefix/acttionPrefix）访问
	 * 
	 * todo 支持正则匹配，自定义路由规则，可以平滑切换，无需改动业务
	 * 
	 * @param unknown $uri
	 * @return string|unknown[]|string[]
	 */
	private  function getRouteParts($uri) {
	    //默认路由
	    if ($uri == '') {
	        $uri = self::$defaultUri;
	    }
	    
	    $uriPartArr = explode ("/", $uri);
	    
	    if (strpos($uri, '/') === false || count($uriPartArr) != self::URIPARTS_COUNT)  {
	        $uriPartArr =  explode('/', self::$defaultUri);
	    }
	    
	    $module = self::formatName($uriPartArr[1]);
	    
	    $controller = $this->createController($module, $uriPartArr[2]);
	    
	    $action = self::formatName($uriPartArr[3], self::ACTION);
	    
	    return [$module, new $controller, $action];
	}
	
	/**
	 * todo 添加自定义路由规则，可以从配置中添加，配置支持yaml、init、php等
	 */
	public static function addRoute()
	{
	    
	}

    /**
     * 创建控制器
     *
     * @param $module
     * @param string $part
     * @return string
     */
	private function createController($module, $part) {
	    $controller = self::formatName($part, self::CONTROLLER);
	    return Application::$controllerNamespace . $module. "\\Controllers\\" . $controller;
	}
	
	/**
	 * 处理RequestUri中间多余的"/"
	 * 
	 * @param string $uri
	 * @return string
	 */
	private function collapseSlashes($uri)
	{
	    return ltrim(preg_replace('#/{2,}#', '/', $uri), '/');
	}
	
	/**
	 * 处理请求RequestUri, 获取格式化之后的Uri部分
	 * 
	 * @return string
	 */
	private function resolveRequestUri()
	{
	    $uri = Ap::$app->request->server('request_uri');
	    if ($uri !== '' && $uri[0] !== '/') {
	        $uri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $uri);
	    }
	    
	    if (($pos = strpos($uri, '?')) !== false) {
	        $uri = substr($uri, 0, $pos);
	    }
	    
	    $uri = urldecode($uri);
	    
	    // 如果不是utf-8编码，则强制转为utf-8 http://w3.org/International/questions/qa-forms-utf-8.html
	    if (!preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $uri)) {
	            $uri = utf8_encode($uri);
	    }	     
	        
        if (substr($uri, 0, 1) === '/') {
            $pathInfo = substr($uri, 1);
        }
        
        return (string) $this->collapseSlashes($uri);
	}
	
	/**
	 * 返回路由各个分段格式化之后的名称
	 * @param string $name
	 * @param string $type
	 * @return string
	 */
	public static function formatName($name, $type = "module") 
	{
		$formatName = "";
		if ($type == self::CONTROLLER) {
			$formatName= ucfirst($name) . Application::POSTFIX_CONTROLLER;
		} else if ($type == self::ACTION) {
			$formatName = ucfirst($name) . Application::POSTFIX_ACTION;
		} else {
			$formatName = ucfirst($name);
		}
		return $formatName;
	}
}
