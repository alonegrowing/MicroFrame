<?php
//namespace Thrift;
namespace MicroFrame\Utils\Billservice;


use Thrift\Transport\TSocket;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;

/*
 * manage thriftconnection
 */

class ThriftConnection
{

    CONST RETRY_TIMES = 2; //连接重试次数

    CONST REV_TIME = 10000;

    CONST SEND_TIME = 10000;

    private $socket;

    private $transport;

    private $protocol;


    /**
     * 连接错误码
     * @var int
     */
    public $connectErrno = 0;

    /**
     * 连接错误信息
     * @var string
     */
    public $connectError;

    public function __construct($socketHost = null, $port = null)
    {

        if (!$socketHost || !$port) {
            \Ap::error(__METHOD__ . 'thrift初始化错误|socketHost or port null' . $socketHost  . "port:" . $port);
            $socketHost = "localhost";
            $port       = "9090";
        }

        $socket = new TSocket($socketHost, $port);
        $socket->setRecvTimeout(self::REV_TIME);  //设置接收超时时间
        $socket->setSendTimeout(self::SEND_TIME);  //设置发送超时时间
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $this->protocol = new TBinaryProtocol($this->transport);
    }

    /**
     * thrift连接
     */
    public function connect()
    {
        //重试操作
        $retry = 0;
        $ret = false;
        do {
            try {
                $this->transport->open();
                $this->connectErrno = 0;
                $this->connectError = null;
                $ret = true;
            } catch (TException $e) {

                \Ap::error(__METHOD__ . 'thrift连接错误 |' . json_encode($e->getMessage()) . '|重试次数=' . $retry);
                //TODO
                $this->connectErrno = $e->getCode();
                $this->connectError = $e->getMessage();
                $ret = false;
            }

        } while (!$ret && ++$retry < self::RETRY_TIMES);

        \Ap::error(__METHOD__ . 'Thrift连接结果,errno:' . $this->connectErrno);

        return $ret;
    }

    /**
     * 断开连接
     */
    public function disconnect()
    {

        try {
            $this->transport->close();
            return true;
        } catch (TException $e) {
            $this->connectErrno = $e->getCode();
            $this->connectError = $e->getMessage();
            return false;
        }
    }

    public function getInput()
    {
        return $this->protocol;
    }


}