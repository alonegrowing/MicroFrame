<?php

namespace MicroFrame\Http;

/**
 * Web 服务的Request请求处理模块，
 * 
 * todo 当前是直接封装Swoole的Reques请求, 下个版本采用psr7 - http-message 规范重写
 * 
 * @author alonegrowing <alonegrowing@gmail.com>
 * 		@date 27/10/20218
 * @version v0.1 
 *
 */
class Request
{
	
	public $request;

    /**
     *
     * Request constructor.
     * @param \Swoole\Http\Request $request
     */
    public function __construct(\Swoole\Http\Request $request)
	{
		$this->request = $request;
	}
	
	/**
	 * 当前请求的Get参数
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) 
	{
		return $this->request->get[$key];
	}
	
	/**
	 * 当前服务的Server信息，request_uri,path_info,ip等等,
	 * @param string $key
	 * @return mixed
	 */
	public function server($key) 
	{
		return $this->request->server[$key];
	}
	
	/**
	 * 返回参数，合并GET 和 POST 参数
	 * @param string $key
	 */
	public function params($key) {
	    $params = array_merge($this->request->get, $this->request->post);
	    return $params[$key];
	}
}
