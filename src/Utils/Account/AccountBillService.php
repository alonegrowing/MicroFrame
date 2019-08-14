<?php

namespace MicroFrame\Utils\Account;



use Ap;
use MicroFrame\Utils\Billservice\BillServiceProxy;
use MicroFrame\Utils\Billservice\BillServiceProxyInterface;


class AccountBillService
{


    /**
     * 添加钻石
     * @param  $uid
     * @param  $gold
     * @param  $source_desc
     * @param  $source_type
     * @param  $ip
     * @return boolean
     */
    public static function addGold($uid, $gold = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type)) {
            Ap::warning('|addGold|参数错误，参数不完整，uid:' . $uid . '|gold:' . $gold . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }

        return self::addMoney($uid, 0, $gold, $source_desc, $source_type, $ip);
    }

    /**
     * 添加映币
     * @param  $uid
     * @param  $point
     * @param  $source_desc
     * @param  $source_type
     * @param  $ip
     * @return boolean
     */
    public static function addPoint($uid, $point = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type)) {
            \Ap::warning('|addPoint|参数错误，参数不完整，uid:' . $uid . '|point:' . $point . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }

        return self::addMoney($uid, $point, 0, $source_desc, $source_type, $ip);
    }

    /**
     * 添加钻石映币变
     * @param  $uid
     * @param  $point
     * @param  $gold
     * @param  $source_desc
     * @param  $source_type
     * @param  $ip
     * @return boolean
     */
    public static function addMoney($uid, $point = 0, $gold = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type)) {
            \Ap::warning(
                '|addMoney|param|【账户加钱】参数错误，参数不完整，uid:' . $uid . '|point:' . $point . '|gold:' . $gold . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }
        $thrift_config = \Ap::$config["thrift"];
        \Ap::warning('|addMoney|获取thrift配置，uid:' . $uid . ' |source_type:' . $source_type . ' |thrift_config:' . json_encode($thrift_config));

        /** @var BillServiceProxyInterface $bill */
        $bill =  BillServiceProxy::getInstance($thrift_config['host'], $thrift_config['port']);
        $bill_id = self::getSeqFromBillId($bill, $uid, $source_type);
        if (empty($bill_id)) {
            \Ap::warning('|addMoney|【账户加钱】创建订单号失败，uid:' . $uid . ' |source_type:' . $source_type . ' |bill_id:' . $bill_id);
            return false;
        }
        //获取对应的md5值
        $source_type_key = SourceTypeMd5::getSourceTypeMd5($source_type);

        if (empty($source_type_key)) {
            \Ap::error( '|addMoney|source_type|not exist|' . $source_type .' |uid:' . $uid . ' |bill_id:' . $bill_id);
            return false;
        }

        try {
            $data = [
                'uid' => $uid,
                'gold' => $gold,
                'point' => $point,
                'bill_id' => $bill_id,
                'source_desc' => $source_desc,
                'source_type' => $source_type,
                'source_type_key' => $source_type_key,
                'trans_time' => time()
            ];
            $billContent = new \BillContent($data);
            $ret = $bill->updateSingleUserMoney($billContent);//rescode, 请求返回状态码，0：表示执行成功；1: default, 默认异常; 2：ID不存在; 3:余额不足; 4:参数异常;
            \Ap::info( '|addMoney|uid:' . $uid . ' |source_type:' . $source_type . " | param:" . json_encode($data) . ' |bill_id:' . $bill_id . '|thrift加钻步骤返回结果 ret:' . json_encode($ret));

            if (empty($ret) || $ret->rescode != 0) {
                //操作失败时,增加报警（严重）
                self::writeLogs("add money fail by thrift , uid:" . $uid . " | param:" . json_encode($data) . '|return ret' . json_encode($ret));
                return FALSE;
            }

            $res = json_decode($ret->res, true);
            $res['bill_id'] = $bill_id;
            return $res;
        } catch (\Exception $e) {
            \Ap::info( '|addMoney|uid:' . $uid . ' |source_type:' . $source_type . " | param:" . json_encode($data) . ' |bill_id:' . $bill_id . '|thrift加钻异常' . json_encode($e->getMessage()));
            return false;
        }
    }

    /**
     * 获取用户当前的映币和钻石数量
     * @param  $uid
     * @param  $source_type
     * @return boolean | array
     */
    public static function getUserGoldPoint($uid, $source_type = 0)
    {
        if (!$uid || !$source_type) {
            return false;
        }
        $source_type_key = SourceTypeMd5::getSourceTypeMd5($source_type);
        try {
            $data = [
                'uid' => $uid,
                'source_type' => $source_type,
                'balance_type' => 0,//0:gold、point均返回,1:返回金币余额,2：返回映币余额
                'is_master' => 0,//查询主从参数,0:默认从库,1:主库
                'source_type_key' => $source_type_key,
            ];
            $thrift_config = \Ap::$config["thrift"];
            \Ap::info('|getUserGoldPoint|获取thrift配置，uid:' . $uid . ' |source_type:' . $source_type . ' |thrift_config:' . json_encode($thrift_config));

            /** @var BillServiceProxyInterface $bill */
            $bill = BillServiceProxy::getInstance($thrift_config['host'], $thrift_config['port']);
            $balanceContent = new \BalanceContent($data);
            $ret = $bill->queryBalance($balanceContent);  //加钻失败？
            \Ap::info( "getUserGoldPoint|请求参数：" . json_encode($data) . "|res=" . json_encode($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            if ($ret && $ret->rescode === 0) {
                return ['uid' => $ret->uid, 'gold' => $ret->gold, 'point' => $ret->point, 'silver' => $ret->silver];
            }
            return false;
        } catch (\Exception $e) {
            \Ap::warning( '查询USER钻石映币量getUserGoldPoint|source_type:' . $source_type . "|uid:" . $uid . "|res:" . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $uid
     * @param int $point 扣映币应传负数（坑）
     * @param $source_desc
     * @param string $source_type
     * @param string $ip
     * @return bool
     * 扣除映币
     */
    public static function delPoint($uid, $point = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type) || $point >= 0) {
            \Ap::warning( '|delPoint|参数错误，参数不完整，uid:' . $uid . '|point:' . $point . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }

        return self::delMoney($uid, $point, 0, $source_desc, $source_type, $ip);
    }

    /**
     * @param $uid
     * @param int $gold
     * @param $source_desc
     * @param string $source_type
     * @param string $ip
     * @return bool 扣除钻石
     * 扣除钻石
     * @internal param $point
     */
    public static function delGold($uid, $gold = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type) || $gold >= 0) {
            \Ap::warning('|delGold|参数错误，参数不完整，uid:' . $uid . '|point:' . $gold . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }

        return self::delMoney($uid, 0, $gold, $source_desc, $source_type, $ip);
    }

    /**
     * @param $uid
     * @param int $point
     * @param int $gold
     * @param $source_desc
     * @param string $source_type
     * @param string $ip
     * @return bool|array
     * 扣除映币
     */
    public static function delMoney($uid, $point = 0, $gold = 0, $source_desc, $source_type = '', $ip = '')
    {
        if (empty($uid) || empty($source_desc) || empty($source_type)) {
            \Ap::warning( '|delMoney|param|【账户扣钱】参数错误，参数不完整，uid:' . $uid . '|point:' . $point . '|gold:' . $gold . '|source_desc:' . $source_desc . '|source_type:' . $source_type);
            return false;
        }

        $thrift_config = \Ap::$config["thrift"];
        \Ap::info( '|delMoney|获取thrift配置，uid:' . $uid . ' |ip:' . $ip . ' |source_type:' . $source_type . ' |thrift_config:' . json_encode($thrift_config));


        /** @var BillServiceProxyInterface $bill */
        $bill = BillServiceProxy::getInstance($thrift_config['host'], $thrift_config['port']);
        $bill_id = self::getSeqFromBillId($bill, $uid, $source_type);
        if (empty($bill_id)) {
            \Ap::warning('|delMoney|【账户扣钱】创建订单号失败，uid:' . $uid . ' |source_type:' . $source_type . ' |bill_id:' . $bill_id);
            return false;
        }
        //获取对应的md5值
        $source_type_key = SourceTypeMd5::getSourceTypeMd5($source_type);
        if (empty($source_type_key)) {
            \Ap::warning('|delMoney|【账户扣钱】md5值为空，uid:' . $uid . ' |source_type:' . $source_type . ' |bill_id:' . $bill_id);
            return false;
        }

        try {
            $data = [
                'uid' => $uid,
                'gold' => $gold,
                'point' => $point,
                'bill_id' => $bill_id,
                'source_desc' => $source_desc,
                'source_type' => $source_type,
                'source_type_key' => $source_type_key,
                'trans_time' => time()
            ];
            $billContent = new \BillContent($data);
            $ret = $bill->updateSingleUserMoney($billContent);//rescode, 请求返回状态码，0：表示执行成功；1: default, 默认异常; 2：ID不存在; 3:余额不足; 4:参数异常;
            \Ap::info('|delMoney|uid:' . $uid . ' |source_type:' . $source_type . " | param:" . json_encode($data) . ' |bill_id:' . $bill_id . '|thrift扣钻步骤返回结果 ret:' . json_encode($ret));

            if (empty($ret)) {
                //操作失败时,增加报警（严重）
                self::writeLogs(
                    "del money fail by thrift , uid:" . $uid . " | param:" . json_encode(
                        $data
                    ) . '|return ret' . json_encode($ret)
                );
                return FALSE;
            }

            if ($ret->rescode != 0) {
                $res = array('bill_id' => '', 'code' => $ret->rescode);
                return $res;
            }

            $res = json_decode($ret->res, true);
            $res['bill_id'] = $bill_id;
            return $res;
        } catch (\Exception $e) {
            \Ap::error('|delMoney|uid:' . $uid . ' |source_type:' . $source_type . " | param:" . json_encode($data) . ' |bill_id:' . $bill_id . '|thrift扣钻异常' . json_encode($e->getMessage()));
            return false;
        }
    }

    /**
     * 获取订单id
     * @param $uid
     * @param $source_type
     * @return string   bill_id ?: ''
     */
    public static function genarateBillId($uid, $source_type)
    {
        $thrift_config = Ap::$config->get("thrift");

        /** @var BillServiceProxyInterface $billObj */
        $billObj = BillServiceProxy::getInstance($thrift_config['host'], $thrift_config['port']);
        $seq = $billObj->getSeqFromBill($uid, $source_type);
        return $seq && $seq->bill_id ? $seq->bill_id : '';
    }

    /**
     * 操作用户的虚拟账户数量
     * @param $uid
     * @param $bill_id
     * @param int $point 操作映币数量 - 0:不操作此项
     * @param int $gold 操作钻石数量 - 0:不操作此项
     * @param string $source_desc
     * @param string $source_type
     * @param string $ip
     * @return mixed false: 接口请求报错， object. object->rescode === 0 success ; other fail
     */
    public static function updateSingleUserMoney(
        $uid,
        $bill_id,
        $point = 0,
        $gold = 0,
        $source_desc = '',
        $source_type = '',
        $ip = ''
    )
    {
        //获取对应的md5值
        $source_type_key = SourceTypeMd5::getSourceTypeMd5($source_type);
        if (empty($source_type_key)) {
            \Ap::warning('|updateSingleUserMoney|【账户剩余金额变动】md5值为空，uid:' . $uid . ' |source_type:' . $source_type . ' |bill_id:' . $bill_id);
            return false;
        }

        $thrift_config =  \Ap::$config["thrift"];

        /** @var BillServiceProxyInterface $billObj */
        $billObj = BillServiceProxy::getInstance($thrift_config['host'], $thrift_config['port']);

        $data = [
            'uid' => $uid,
            'bill_id' => $bill_id,
            'source_type' => $source_type,
            'source_type_key' => $source_type_key,
            'point' => $point,
            'source_desc' => $source_desc,
            'trans_time' => time(),
            'user_ip' => $ip,
        ];
        $billContent = new \BillContent($data);
        $ret = $billObj->updateSingleUserMoney($billContent);
        \Ap::info( '|updateSingleUserMoney|uid:' . $uid . ' |source_type:' . $source_type . " | param:" . json_encode($data) . ' |bill_id:' . $bill_id . '|thrift操作钻石步骤返回结果 ret:' . json_encode($ret));

        if (empty($ret)) {
            //操作失败时,增加报警（严重）
            self::writeLogs(
                "updateSingleUserMoney fail by thrift , uid:" . $uid . " | param:" . json_encode(
                    $data
                ) . '|return ret' . json_encode($ret)
            );
            return FALSE;
        }
        return $ret; //$ret->rescode === 0.success ; other fail
    }

    /**
     * 获取订单id
     * @params uid 用户ID
     * @param BillServiceProxyInterface $billObj
     * @param $uid
     * @param $source_type
     * @return string
     */
    private static function getSeqFromBillId( $billObj, $uid, $source_type)
    {
        $seq = $billObj->getSeqFromBill($uid, $source_type);
        $bill_id = $seq->bill_id;

        //生成订单失败时,增加报警（严重）
        if (empty($bill_id)) {
            self::writeLogs(
                "create order fail by thrift , uid:" . $uid . " | source_type:" . $source_type . '|return seq:' . json_encode($seq)
            );
        }
        \Ap::info('|getSeqFromBillId|【账户加钱】创建订单号，uid:' . $uid . ' |source_type:' . $source_type . ' |seq:' . json_encode($seq));
        return !empty($bill_id) ? $bill_id : '';
    }

    /**
     * @param $msg
     * @param int $time
     * @param int $count
     * 报警log
     */
    private static function writeLogs($msg, $time = 60, $count = 1)
    {
        $path = ROOT_PATH . "/logs/account_bill_service." . date('Y-m-d') . ".log";
        $content = $time . "\t" . $count . "\t" . date(
                'Y-m-d H:i:s'
            ) . "\t" . 'micro.frame' . "\t" . 'AccountBillService' . "\t" . '[' . $msg . ']' . "\t" . '[' . $msg . ']' . "\n";
        file_put_contents($path, $content, FILE_APPEND);
    }

}