<?php
namespace MicroFrame\Http;

/**
 * Session的使用管理
 * @author alonegrowing <alonegrowing@gmail.com>
 * @date 09/01/2019
 *
 */
use Ap;
use MicroFrame\Helper\MGeneral;
class Session {
    
    /**
     * @var string $cache_prefix session_id的key的前缀
     */
    public $cachePrefix = 'phpsess_';
    
    /**
     * @var string $cookieKey cookie的session的key
     */
    public $cookieKey = 'PHPSESSID';
    
    /**
     * @var string $cacheDriver session的 缓存驱动, 默认使用redis存储
     */
    public $cache_driver = 'redis';
    
    /**
     * @var object $driver 缓存驱动的实例 
     */
    public $driver = null;
    
    /**
     * @var array $session 寄存session的数据
     */
    public $_G_SESSION = [];
    
    /**
     * @var cookie的设置
     */
    public  $cookieLifetime = 7776000;
    public  $sessionLifetime = 0;
    public  $cookieDomain = '*';
    public  $cookiePath = '/';
    
    /**
     * @var boolean $isStart 是否已经启动session
     */
    public $isStart = false;
    
    /**
     * @var string $session_id sessio_id
     */
    public $sessionId;
    
    /**
     * @var boolean 是否为只读，只读不需要保存
     */
    public $readonly;
    
    /**
     * Session的配置初始化
     * @param array $config
     */
    public function __construct($config = []) {
        $config = array_merge($config, Ap::$config['session']);
        if (empty($config)) {
            Ap::trace("Session配置参数为空", $config);
        }
        
        if(isset($config['cache_prefix'])  && !empty($config['cache_prefix'])) {
            $this->cachePrefix = $config['cache_prefix'];
        }
        if(isset($config['cookie_key ']) && !empty($config['cookie_key '])) {
            $this->coookieKey = $config['cookie_key '];
        }
        if(isset($config['cookie_lifetime']) && !empty($config['cookie_lifetime'])) {
            $this->cookieLifetime = $config['cookie_lifetime'];
        }
        if(!isset($config['session_lifetime'])) {
            $this->sessionLifetime = $this->cookie_lifetime + 7200;
        }else {
            if($config['session_lifetime'] <  $config['cookie_lifetime']) {
                $this->sessionLifetime = $this->cookie_lifetime + 7200;
            }else {
                $this->sessionLifetime = $config['session_lifetime'];
            }
        }
        if(isset($config['cookie_path']) && !empty($config['cookie_path'])) {
            $this->cookiePath = $config['cookie_path'];
        }
        if(isset($config['cache_driver']) && !empty($config['cache_driver'])) {
            $this->cacheDriver = $config['cache_driver'];
        }
        if(isset($config['cache_driver'])) {
            $this->cookieDomain = $config['cookie_domain'];
        }  
    }
    
    /**
     * Session功能启用
     * @return boolean
     */
    public function start() {     
        $this->isStart = true;
        
        $cookieSessionId = Ap::$app->request->cookie[$this->cookieKey] ?? NULL;
        
        $this->sessionId = $cookieSessionId;
        
        if(empty($cookieSessionId)) {
            $sessId = MGeneral::randmd5(40);
            Ap::$app->response->cookie($this->cookieKey, $sessId, time() + $this->cookieLifetime, $this->cookiePath, $this->cookieDomain);
            $this->sessionId = $sessId;
        }
        
        $this->_G_SESSION = $this->load($this->sessionId);
        return true;
    }
    
    /**
     * load 加载获取session数据
     * @param    string  $sess_id
     * @return   array
     */
    protected function load($sessId) {
        if(!$this->sessionIdd) {
            $this->sessionIdd = $sessId;
        }
        
        $data = $this->driver->get($this->cachePrefix . $sessId);
        
        //先读数据，如果没有，就初始化一个
        if (!empty($data)) {
            return unserialize($data);
        }else {
            return [];
        }
    }
    
    /**
     * 保存Session
     * @return bool
     */
    public function save() {
        if(!$this->isStart || $this->readonly) {
            return true;
        }
        //设置为Session关闭状态
        $this->isStart = false;
        $sessionKey = $this->cachePrefix . $this->sessionId;
        
        // 如果没有设置SESSION,则不保存,防止覆盖
        if(empty($this->_G_SESSION)) {
            return false;
        }
        return $this->driver->setex($sessionKey, $this->sessionLifetime, serialize($this->_G_SESSION));
    }
    
    /**
     * getSessionId 获取session_id
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }
    
    /**
     * set 设置session保存数据
     * @param   string   $key
     * @param   mixed  $data
     * @return    true
     */
    public function set(string $key, $data) {
        if(is_string($key) && isset($data)) {
            $this->_G_SESSION[$key] = $data;
            return true;
        }
        return false;
    }
    
    /**
     * get 获取session的数据
     * @param   string  $key
     * @return   mixed
     */
    public function get(string $key = null) {
        if(is_null($key)) {
            return $this->_G_SESSION;
        }
        return $this->_G_SESSION[$key];
    }
    
    /**
     * has 是否存在某个key
     * @param    string  $key
     * @return   boolean
     */
    public function has(string $key) {
        if(!$key) {
            return false;
        }
        return isset($this->_G_SESSION[$key]);
    }
    
    /**
     * getSessionTtl 获取session对象的剩余生存时间
     * @param   bool  $formatDate 是否格式化
     * @return
     */
    public function getSessionTtl() {
        $session_key = $this->cachePrefix . $this->sessionId;
        $isExists = $this->driver->exists($session_key);
        $isExists && $ttl = $this->driver->ttl($session_key);
        if($ttl >= 0) {
            return $ttl;
        }
        return null;
    }
    
    /**
     * delete 删除某个key
     * @param    $key [description]
     * @return   boolean
     */
    public function delete(string $key) {
        if($this->has($key)) {
            unset($this->_G_SESSION[$key]);
            return true;
        }
        return false;
    }
    
    /**
     * clear 清空某个session
     * @param
     * @return
     */
    public function destroy() {
        if(!empty($this->_G_SESSION)) {
            $this->_G_SESSION = [];
            // 使cookie失效
            setcookie($this->cookieKey, $this->sessionId, time() - 600, $this->cookiePath, $this->cookieDomain);
            // redis中完全删除session_key
            $session_key = $this->cachePrefix . $this->sessionId;
            return $this->driver->del($session_key);
        }
        return false;
    }
    
    /**
     * reGenerateSessionId 重新生成session_id
     * @param    boolean   $ismerge  生成新的session_id是否继承合并当前session的数据，默认true,如需要产生一个完全新的空的$this->_SESSION，可以设置false
     * @return   void
     */
    public function reGenerateSessionId($ismerge=true) {
        $sessionData = $this->_G_SESSION;
        
        // 先cookie的session_id失效
        setcookie($this->cookieKey, $this->sessionId, time() - 600, $this->cookiePath, $this->cookieDomain);
        
        // 设置session_id=null
        $this->sessionId = null;
        
        // 产生新的session_id和返回空的$_SESSION数组
        $this->start();
        
        if ($ismerge) {
            $this->_G_SESSION = array_merge($this->_G_SESSION, $sessionData);
        }
    } 
}