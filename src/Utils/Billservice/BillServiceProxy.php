<?php
//namespace Thrift;
namespace MicroFrame\Utils\Billservice;

use Thrift\Exception\TException;

require_once __DIR__.'/BillService.php';
require_once __DIR__.'/Types.php';
/**
 * 动态代理类
 * @author win7
 *
 */
class BillServiceProxy {


    /**
     * @var $_instance BillServiceProxy
     */
    public  static $_instance = null;
    
    CONST BUSINESS_RETRY_TIMES = 2;
    CONST EXPIRE_TIMEOUT       = 5;

    protected $expireTime ;


    
    protected $billService;
    
    protected $connection = null;
    
    protected $isConnection;
    
    protected $errno = 0;
    
    protected $error;


    public function __construct($socketHost=null,$port=null){
        $this->connection = new ThriftConnection($socketHost, $port);
        $this->expireTime = time() + self::EXPIRE_TIMEOUT;  //过期时间
    }

    public static function getInstance($socketHost = null,$port = null) {
        if (static::$_instance === null ||  time() > self::$_instance->expireTime || !(static::$_instance instanceof self)) {
            static::$_instance = new self($socketHost,$port);
        }

        return static::$_instance;
    }

    /**
     *
     * @param string $func
     * @param array $args
     * @return bool
     */
    public function __call($func,$args){
        
        //调用不存在的方法是调用        
        if(!$this->connection->connect()) return false;
        
        $billService = new \BillServiceClient($this->connection->getInput());
        
        $retry = 0 ;
        $res = false;
        
        do{
            try{
                $count =  count($args); // 1和2个参数的interface
                if($count == 1){
                    $res = $billService->$func($args[0]);
                }elseif ($count==2){
                    $res = $billService->$func($args[0],$args[1]);
                }
                $this->errno = 0;
                $this->error = null;
                
            }catch (TException $e){
                \Ap::error(__METHOD__ . 'Thrift业务错误 |'.  json_encode($e->getMessage()).'|重试次数='.$retry);
                $this->errno = $e->getCode();
                $this->error = $e->getMessage();
                $res = false;
            }
        }while (!$res && ++$retry<self::BUSINESS_RETRY_TIMES);
        
        $this->connection->disconnect();
        \Ap::error(__METHOD__ . 'Thrift业务结果,errno:'.$this->errno);
        
        return $res;        
    }


}

// Just Just for IDE undefined Function !!!
interface BillServiceProxyInterface {

    public function getSeqFromBill($uid, $source_type);

    public function updateSingleUserMoney($count);

    public function queryBalance($balanceContent);
}

