<?php
namespace MicroFrame\Http;

use Ap;
use MicroFrame\Http\Base\Exception;

/**
 * Controller 是所有其他控制器的基类
 * 
 * @author alonegrowing <alonegrowing@gmail.com>
 * 		@date 27/10/2018
 *
 */
class Controller 
{
	
	
	/**
	 * 运行当前控制器的action方法
	 * 
	 * @param string $action
	 */
	public function runAction($action) 
	{
		try {
			if(method_exists($this, $action)) {
                $serverInfo = Ap::$app->request->request->server;
				Ap::info("access|" .$serverInfo["request_uri"] . '?' . $serverInfo["query_string"] );
				call_user_func_array([&$this, $action], []);
			} else {
				Ap::$app->response->showError();
			}			
		} catch (Exception $e) {
			echo $e->getMessage();
		}	
	}
}