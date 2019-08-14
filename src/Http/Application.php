<?php

namespace MicroFrame\Http;

use Ap;
use MicroFrame\Routing\Router;
use MicroFrame\Base\ErrorHandler;


/**
 * Application 是其他所有web应用的基类 
 *
 * @property Request $request  请求Request对象
 * @property Response $response 请求响应对象，只读
 *
 * @author alonegrowing <alonegrowing@gmail.com>
 * @date 27/10/2018
 * @since 0.1.0
 */
class Application 
{
	
	/**
	 * @var string 应用程序的版本号
	 */
	const VERSION = '0.1.0';
	
	/**
	 * @var string 微服务框架控制器的后缀
	 */
	const POSTFIX_CONTROLLER = "Controller";
	
	/**
	 * @var string 微服务框架控制器方法的后缀
	 */
	const POSTFIX_ACTION = "Action";
	
	/**
	 * @var string 控制器命名空间的前缀，控制器的命名空间会根据该前缀拼接而成
	 * 
	 * 默认的控制器命名空间前缀是 `App\\`.
	 */
	public static $controllerNamespace = 'App\\';
	
	/**
	 * @var object 请求Request对象 
	 */
	public $request;
	
	/**
	 * @var object 请求Response对象
	 */
	public $response;
	
	/**
	 * @var object 当前请求的模块
	 */
	public $module;
	
	/**
     * @var Controller 当前活跃的控制器
     */
    public $controller;
		
    /**
     * @var string 当前应用的Action
     */
	public $action;
	
	/**
	 * @var string 应用的名称
	 */
	public $name = 'My Application';
	
	/**
	 * @var string 应用程序的编码，默认是utf-8
	 */
	public $charset = 'UTF-8';
	
	/**
	 * @var string 当前应用程序的运行环境，默认是dev开发环境
	 */
	public $environment = 'dev';


	public function __construct(Request $request, Response $response) 
	{
		Ap::$app = $this;
		
		ErrorHandler::getInstance()->register();
		
		$this->request = $request;
		$this->response = $response;
	}	

	/**
     * 运行当前应用程序，是当前应用的主要入口
     * 
     * @return int the exit status (0 means normal, non-zero values mean abnormal)
     */
	public function run() 
	{
		try {
			$uriParts = Router::getInstance()->parse();
			if (empty($uriParts)) {
				$this->response->renderError(1);
				return 0;
			}
			
			list($this->module, $this->controller, $this->action) = $uriParts;
			
			$this->handleRequest();	
			
			return 0;
		} catch (\Exception $e) {
			
		}		
	}

	/**
     * 处理当前指定的请求
     * 
     * @throws NotFoundHttpException if the requested route is invalid
     */
	public function handleRequest() 
	{
		$this->controller->runAction($this->action);
	}
	
}