<?php
/**
 *
 * @author  wangg <alonegrowing@gmail.com>
 * @since   2018-11-30
 * @version v0.1.0
 */
namespace MicroFrame\Utils\Kafka;

abstract class BaseKafka {

    abstract protected function run();

    //消息处理方法 | 需要上层子类实现
    abstract public function handle($data);
}
