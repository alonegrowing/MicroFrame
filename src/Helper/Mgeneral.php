<?php
namespace MicroFrame\Helper;

/**
 * 基于Swoole的辅助工具类
 * @author alonegrowing <alonegrowing@gmail.com>
 * @date 09/01/2019
 */
use Ap;
class MGeneral  {
    
    /**
     * 判断是否是加密请求
     * @return boolean
     */
    public static function isSsl() {
        $request = Ap::$app->request;
        
        if(isset($request->server['HTTPS']) && ('1' == $request->server['HTTPS'] || 'on' == strtolower($request->server['HTTPS']))){
            return true;
        }elseif(isset($request->server['SERVER_PORT']) && ('443' == $request->server['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }
    
    /**
     * 判断是否是来自于手机端的请求
     * @return boolean
     */
    public static function isMobile() {
        $request = Ap::$app->request;
        
        if (isset($request->server['HTTP_VIA']) && stristr($request->server['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($request->server['HTTP_ACCEPT']) && strpos(strtoupper($request->server['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($request->server['HTTP_X_WAP_PROFILE']) || isset($request->server['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($request->server['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $request->server['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
    
   /**
    * 获取本地ip不包括端口
    * @return array
    */
    public static function getLocalIp() {
        return swoole_get_local_ip();
    }
    
    /**
     * getClientIP 获取客户端ip
     * @param   int  $type 返回类型 0:返回IP地址,1:返回IPV4地址数字
     * @return  string
     */
    public static function getClientIP($type=0) {
        $request = Ap::$app->request;
        
        // 通过nginx的代理
        if(isset($request->server['HTTP_X_REAL_IP']) && strcasecmp($request->server['HTTP_X_REAL_IP'], "unknown")) {
            $ip = $request->server['HTTP_X_REAL_IP'];
        }
        if(isset($request->server['HTTP_CLIENT_IP']) && strcasecmp($request->server['HTTP_CLIENT_IP'], "unknown")) {
            $ip = $request->server["HTTP_CLIENT_IP"];
        }
        if (isset($request->server['HTTP_X_FORWARDED_FOR']) and strcasecmp($request->server['HTTP_X_FORWARDED_FOR'], "unknown"))
        {
            return $request->server['HTTP_X_FORWARDED_FOR'];
        }
        if(isset($request->server['REMOTE_ADDR'])) {
            //没通过代理，或者通过代理而没设置x-real-ip的
            $ip = $request->server['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }
    
    /**
     * isValidateEmail 判断是否是合法的邮箱
     * @param    string  $email
     * @return   boolean
     */
    public static function isValidateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * isValidateIp 判断是否是合法的的ip地址
     * @param    string  $ip
     * @return   boolean
     */
    public static function isValidateIp($ip) {
        $ipv4 = ip2long($ip);
        if(is_numeric($ipv4)) {
            return true;
        }
        return false;
    }
    
    /**
     * roundByPrecision 四舍五入
     * @param    float  $number    数值
     * @param    int    $precision 精度
     * @return   float
     */
    public static function roundByPrecision($number, $precision) {
        if (strpos($number, '.') && (strlen(substr($number, strpos($number, '.')+1)) > $precision))
        {
            $number = substr($number, 0, strpos($number, '.') + 1 + $precision + 1);
            if (substr($number, -1) >= 5)
            {
                if ($precision > 1)
                {
                    $number = substr($number, 0, -1) + ('0.' . str_repeat(0, $precision-1) . '1');
                }
                elseif ($precision == 1)
                {
                    $number = substr($number, 0, -1) + 0.1;
                }
                else
                {
                    $number = substr($number, 0, -1) + 1;
                }
            }
            else
            {
                $number = substr($number, 0, -1);
            }
        }
        return $number;
    }
    
    /**
     * _die 异常终端程序执行
     * @param    string   $msg
     * @param    int      $code
     * @return   mixed
     */
    public static function _die($html='', $msg='') {
        // 直接结束请求
        Ap::$app->response->end($html);
        throw new \Exception($msg);
    }
    
    /**
     * string 随机生成一个字符串
     * @param   int  $length
     * @param   bool  $number 只添加数字
     * @param   array  $ignore 忽略某些字符串
     * @return string
     */
    public static function string($length = 8, $number = true, $ignore = []) {
        //字符池
        $strings = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        //数字池
        $numbers = '0123456789';
        if ($ignore && is_array($ignore)) {
            $strings = str_replace($ignore, '', $strings);
            $numbers = str_replace($ignore, '', $numbers);
        }
        $pattern = $strings . $numbers;
        $max = strlen($pattern) - 1;
        $key = '';
        for ($i = 0; $i < $length; $i++)
        {
            //生成php随机数
            $key .= $pattern[mt_rand(0, $max)];
        }
        return $key;
    }
    
    /**
     * idhash 按id计算散列值
     * @param   int  $uid
     * @param   int  $base
     * @return integer
     */
    public static function idhash($uid, $base = 100) {
        return intval($uid / $base);
    }
    
    /**
     * randtime 按UNIX时间戳产生随机数
     * @param   int  $rand_length
     * @return string
     */
    public static function randtime($rand_length = 6) {
        list($usec, $sec) = explode(" ", microtime());
        $min = intval('1' . str_repeat('0', $rand_length - 1));
        $max = intval(str_repeat('9', $rand_length));
        return substr($sec, -5) . ((int)$usec * 100) . mt_rand($min, $max);
    }
    
    /**
     * randmd5 产生一个随机MD5字符的一部分
     * @param   int  $length
     * @param   int  $seed
     * @return string
     */
    public static function randmd5($length = 20, $seed = null) {
        if (empty($seed)) {
            $seed = self::string(20);
        }
        return substr(md5($seed . mt_rand(111111, 999999)), 0, $length);
    }
    
    /**
     * mbstrlen 计算某个混合字符串的长度总数，包含英文或者中文的字符串,如果安装mb_string扩展的话，可以直接使用mb_strlen()函数，与该函数等效
     * @param    string  $str
     * @return   int
     */
    public static function mbStrlen($str) {
        // strlen()计算的是字节
        $len = strlen($str);
        if ($len <= 0) {
            return 0;
        }
        
        $count = 0;
        for($i = 0; $i < $len; $i++) {
            $count++;
            if(ord($str{$i}) >= 0x80) {
                $i += 2;
            }
        }
        return $count;
    }
    
    /**
     * 获取
     * @return  string
     */
    public static function getUserAgent() {
        return Ap::$app->request->server['HTTP_USER_AGENT'];
    }
    
}