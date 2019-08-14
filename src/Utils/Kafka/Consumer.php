<?php
/**
 *
 * @author  wangg <alonegrowing@gmail.com>
 * @since   2018-11-30
 * @version v0.1.0
 */

namespace MicroFrame\Utils\Kafka;

abstract class Consumer extends BaseKafka
{

    //kafka broker list
    public $brokerList;

    //kafka topic
    public $topicName;

    //kafka auto_offset_reset
    public $autoOffsetReset;

    //kafka group id
    public $groupId;

    protected $run = true;


    public $config ;




    public function _init() {
        $this->brokerList = $this->config["brokerList"];
        $this->topicName  = $this->config["topic"];
        $this->groupId    = $this->config["groupId"];
    }

    public function __construct($config = array())
    {

        $this->config = $config;

        \Ap::info("start::",$config);
        $this->_init();
        $this->autoOffsetReset = "smallest";

    }

    public function run()
    {
        // TODO: Implement run() method.


        //获取消费者实例，获取后续数据
        $consumer = $this->_getKafkaConsumer();
        $timeOutCount = 0;
        while ($this->run) {
            $message = $consumer->consume(120 * 1000);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $time_out_count = 0;
                    $this->_messageHandle($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $msg = 'No more messages; will wait for more';
                    \Ap::info($msg);
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    $timeOutCount++;
                    $msg = 'Timed out' ;
                    \Ap::info($msg);
                    break;
                default:
                    \Ap::info("consumer|run|error:" . $message->err);
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
            if ($timeOutCount >= 3) {
                $msg = 'Timed out >= 3';
                \Ap::info($msg);
                $this->run = false;
                break;
            }

            usleep(100);
        }
    }

    protected function _messageHandle($message)
    {
        print_r($message);
        $data = json_decode($message->payload, true);

        //todo handler
        $this->handle($data);

        print_r($data);

        usleep(100);
    }

    private function _getKafkaConsumer()
    {
        $conf = new \RdKafka\Conf();
        // Set a rebalance callback to log partition assignemts (optional)
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(NULL);
                    break;

                default:
                    throw new \Exception($err);
            }
        });

        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', $this->groupId);

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', implode(",",$this->brokerList));

        $topicConf = new \RdKafka\TopicConf();

        // Set where to start consuming messages when there is no initial offset in offset store or the desired offset is out of range. 'smallest': start from the beginning 'largest' : new
        $topicConf->set('auto.offset.reset', $this->autoOffsetReset);

        // Set the configuration to use for subscribed/assigned topics
        $conf->setDefaultTopicConf($topicConf);

        $consumer = new \RdKafka\KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $consumer->subscribe([$this->topicName]);

        $handleShutdown = function() use ($consumer) {
            $consumer->unsubscribe();
        };
        register_shutdown_function($handleShutdown);

        return $consumer;
    }

}