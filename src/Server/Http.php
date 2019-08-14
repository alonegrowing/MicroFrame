<?php
namespace MicroFrame\Server;

use Ap;
use Swoole\Http\Server;
use MicroFrame\Http\Request;
use MicroFrame\Http\Response;
use MicroFrame\Http\Application;
use Noodlehaus\Config;


/**
 * Http服务创建控制器
 * 
 * @author Levin <alonegrowing@gmail.com>
 *         @date 25/10/2018
 * @version v0.1
 *         
 */
class Http  
{
	
	private $server = null;

	public $apBeginTime;
		
    /**
     * @var Http 实例
     */
    private static $_instance;

    const EXTENSION_NAME = "inkebalance.so";

    private function __construct(){}    
 
    /**
     * 返回Http的单例
     * @return Http
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
	
	/**
	 * 创建Http服务器，处理request请求hope
	 *
	 * 配置文件解析，初始化；使用：Ap::$config->get('route.rule')
	 */
	public function run(): Server 
	{
		Ap::writePid();
		
	    $this->setApBeginTime();

	    Ap::$config = new Config ( defined ( 'CONFIG_DIR' ) ? CONFIG_DIR : [
	        ROOT_PATH . '/config',
	        ROOT_PATH . '/config/' . AP_ENV
	    ] );
        //Ap::OrmStart(Ap::$config["db.news"]); //支持orm的db驱动初始化
        Ap::MinDbStart(Ap::$config["db.news"]); //轻量级、优雅的PDO封装
        Ap::trace('服务启动');



        $this->server = new Server ( Ap::$config->get('http.socket'), Ap::$config->get('http.port') );
        
        /**
         * 设置swoole运行时参数
         */
        $this->server->set(array(
            'worker_num' => Ap::$config["server.swoole.worker_num"], //worker进程数量
        ));

        $this->server->on('Start', function (\Swoole\Http\Server $server) {
            echo "Onstart...\n";
        });

        // 服务器启动时执行一次   worker 中的 ManagerStart | WorkerStart 事件是并发执行的, 不一定按顺序来
        // ManagerStart 可能在 WorkerStart 之后执行
        $this->server->on('ManagerStart', function (\Swoole\Http\Server $server) {
            echo 'ManagerStart.... ' . PHP_EOL ;
        });

        // 服务器关闭时执行一次
        $this->server->on('workerStart', function (\Swoole\Http\Server $server,$worker_id) {
            $this->SoaInit($worker_id);
        });

        // 服务器关闭时执行一次 | 是否需要引入
        $this->server->on('Shutdown', function (\Swoole\Http\Server $server) {
            $this->Unregister();
        });







		$this->server->on ( 'Request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) 
		{		
		    $this->setApBeginTime();
			/**
			 * 启动App运行
			 * @var Application $app
			 */
			$app = new Application(new Request($request), new Response($response));
			$app->run();

		});

		$this->server->start();
	}

    /**
     * @param $worker_id
     */
    public function SoaInit($worker_id) {

        if (!extension_loaded(self::EXTENSION_NAME)) {
            dl(self::EXTENSION_NAME);
        }

        //init......
        inkebalance_initialize(
            Ap::$config["serviceRegister.serviceName"],
            Ap::$config["serviceRegister.consulClient"],
            Ap::$config["serviceRegister.serviceProto"],
            "./logs/balance.log",
            "debug"
        );

        //watch......
        if (is_array(Ap::$config["server.soa"])) {
            foreach (Ap::$config["server.soa"] as $key => $serverItem) {
                inkebalance_watch_service($serverItem);
            }
        }


        //register ......
        if ($worker_id >= (Ap::$config["server.swoole.worker_num"]) - 1) {
            if (is_array(Ap::$config["serviceRegister"])) {

                inkebalance_register(
                    Ap::$config["serviceRegister.serviceProto"],
                    Ap::$config->get('http.port'),
                    array()
                );
                Ap::trace('SoaInit', Ap::$config["serviceRegister"]);
            }
        }


    }

    public function Unregister() {
        if (!extension_loaded(self::EXTENSION_NAME)) {
            dl(self::EXTENSION_NAME);
        }
        inkebalance_deregister();
    }
	
	/**
	 * 请求的开始时间
	 */
	public function setApBeginTime() {
	    $this->apBeginTime = microtime(true);
	}
	
	/**
	 * 获取请求的开始时间
	 */
	public function getApBeginTime() {
	    return $this->apBeginTime;
	}
}