<?php
/**
 * Session context
 * 
 * Session 数据容器类
 */
class Doggy_Session_Context {
    private $_data=array();
    private static $_session_id;
    /**
     * @var Doggy_Session_Context
     */
    protected static $_instance;
    /**
     * Session存储层
     *
     * @var Doggy_Session_Storage
     */
    private $_storage;
    public function __construct($sessionId=null){
        
        $session_setting = Doggy_Config::get('app.session');
        $class = $session_setting['class'];
        if(!class_exists($class)){
            throw new Doggy_Exception('app.session not set class');
        }
        $obj = new $class();

        $this->_storage = $obj;
        if(!is_null($sessionId)){
             $this->setSessionId($sessionId);
        }
        $session_domain = isset($session_setting['cookie_domain'])?$session_setting['cookie_domain']:null;
        $session_path = isset($session_setting['cookie_path'])? $session_setting['cookie_path']:'/';
        $session_ttl = isset($session_setting['ttl'])? $session_setting['ttl'] : 0;
        $session_name = isset($session_setting['session_name'])? $session_setting['session_name']: Doggy_Config::get('app.id').'_sid';
        
        session_name($session_name);
        session_set_cookie_params($session_ttl,$session_path,$session_domain);
        
        @session_start();
        self::$_session_id = session_id();
        $this->_data = $this->_storage->init();
        Doggy_Log_Helper::debug("initialize session storage class:$class");
    }
    
    
    /**
     * 将session数据回写到后端存储层
     *
     */
    public function __destruct(){
        $this->flush();
    }
    /**
     * 返回session中指定key的数据
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key){
        return isset($this->_data[$key])?$this->_data[$key]:null;
    }
    /**
     * 设置session中的数据
     *
     * @param string $key
     * @param mixed $value
     * @return Doggy_Session_Context
     */
    public function set($key,$value){
        $this->_data[$key] = $value;
        return $this;
    }
    /**
     * 删除session中指定key的数据
     * @param string $key
     * @return Doggy_Session_Context
     */
    public function remove($key){
        return $this->set($key,null);
    }
    
    /**
     * 清除Session数据
     * 默认为清除当前Session的数据
     * 如id不为空则清除指定id的session的全部数据
     * 
     * @param string $key
     * @return Doggy_Session_Context
     */
    public function destory($id=null){
        if(!is_null($id)){
            $_id = self::$_session_id;
            $this->setSessionId($id);
        }else{
            //当前session
            $this->_data=array();
        }
        Doggy_Log_Helper::debug("destory session::".self::$_session_id);
        $this->_storage->store(array());
        if(!is_null($id)){
            $this->setSessionId($_id);
        }
        return $this;
    }
    
    public function flush(){
        $this->_storage->store($this->_data);
    }
    /**
     * 重新初始化当前的session_context
     */
    public static function restart($sessionId=null){
       if(!is_null(self::$_instance)){
           self::$_instance->flush();
           self::$_instance=null;
       }
       self::$_instance = new Doggy_Session_Context($sessionId); 
    }
    /**
     * alias of Doggy_Session_Context::context()
     * 
     * @return Doggy_Session_Context
     */
    public static function getContext($session_id=null){
        if(is_null(self::$_instance)){
            self::$_instance = new Doggy_Session_Context($session_id);
        }
        return self::$_instance;
    }
    
    /**
     * factory current session context
     *
     * @param string $session_id 
     * @return Doggy_Session_Context
     */
    public static function context($session_id=null) {
        if(is_null(self::$_instance)){
            self::$_instance = new Doggy_Session_Context($session_id);
        }
        return self::$_instance;
    }
    
    /**
     * Session中是否有指定key的数据
     * 
     * @return boolean
     */
    public function has($key){
        return isset($this->_data[$key]);
    }
    /**
     * 返回当前的SessionId
     */
    public function getSessionId(){
        return self::$_session_id;
    }
    /**
     * 设置SessionId
     */
    public function setSessionId($id){
        self::$_session_id = $id;
        @session_id($id);
    }
}
?>