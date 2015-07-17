<?php
/**
 * The Doggy_Dispatcher_Context is the context in which an Doggy_Action is executed. Each context is basically a
 * container of objects an action needs for execution like the session, parameters, locale, etc.
 *
 * Doggy_Dispatcher_Context is singleton, The benefit of this is you don't need to worry about a user specific action context, you just get it:
 * <p/>
 * <ul><code>$context = Doggy_Dispatcher_Context::getContext();</code></ul>
 * <p/>
 *
 * @version $Id: Context.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 */
class Doggy_Dispatcher_Context {

    protected  static $context;

    private $stash;
    private $request;
    private $response;
    
    protected function __construct(){
        $this->stash = array();
        $this->request = null;
        $this->response = null;
    }

    /**
     * Get current singleton ActionContext object
     *
     * @return Doggy_Dispatcher_Context
     */
    public static function getContext($class=NULL){
        if(is_null(self::$context)){
            if(is_null($class)){
                $class=  __CLASS__;
            }
            self::$context = new $class();
        }
        return self::$context;
    }
    /**
     * 获得当前Request对象
     *
     * @return Doggy_Dispatcher_Request_Abstract
     */
    public function getRequest(){
        return $this->request;
    }
    /**
     * 获得当前容器内的Response对象
     *
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function getResponse(){
        return $this->response;
    }
    /**
     * 返回application level的属性信息
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     * @deprecated
     */
    public function get($name,$default=null){
        return isset($this->stash[$name])?$this->stash[$name]:$default;
    }
    /**
     * 设置application level的属性信息
     *
     * @param string $name
     * @param mixed $value
     * @return Doggy_Dispatcher_Context
     * @deprecated
     */
    public function set($name,$value){
        $this->stash[$name] = $value;
        return $this;
    }
    /**
     * Alias of set
     *
     * @param string $name
     * @param mixed $value
     * @return Doggy_Dispatcher_Context
     * @deprecated
     */
    public function put($name,$value){
        $this->stash[$name] = $value;
        return $this;
    }
    /**
     * Remove the data from ActionScope
     * 
     * @param $name
     * @return Doggy_Dispatcher_Context
     * @deprecated
     */
    public function remove($name){
        unset($this->stash[$name]);
        return $this;
    }
    /**
     * 清除ActionScope的数据
     * @return Doggy_Dispatcher_Context
     */
    public function clear(){
        $this->stash = array();
    }
    /**
     * 清除全部的数据
     * @return Doggy_Dispatcher_Context
     */
    public function clearAll(){
        $this->stash = array();
        unset($this->request);
        unset($this->response);
    }
    /**
     * 返回Action范围的Context属性数组
     *
     * @return array
     * @deprecated
     */
    public function getAll(){
        return $this->stash;
    }
    /**
     * 添加全部数组到action范围
     *
     * @param array $values
     * @deprecated
     * @return Doggy_Dispatcher_Context
     */
    public function putAll(array $values){
        $this->stash = array_merge($this->stash,$values);
        return $this;
    }
    /**
     * 设置Resultl的属性
     *
     * @param string $name
     * @param mixed $value
     * @deprecated
     * @return Doggy_Dispatcher_Context
     */
    function putResult($name,$value){
        $this->stash['_view'][$name] = $value;
        return $this;
    }
    /**
     * Alias of putResult
     *
     * @param string $name
     * @param mixed $value
     * @return Doggy_Dispatcher_Context
     */
    function setResult($name,$value){
        $this->stash['_view'][$name] = $value;
        return $this;
    }
    /**
     * 获得Result的指定$name的属性值
     *
     * @param string $name
     * @param mixed $default
     * @deprecated
     * @return mixed
     */
    function getResult($name,$default=null){
        return isset($this->stash['_view'][$name])?$this->stash['_view'][$name]:$default;
    }
    /**
     * 设置当前Request对象
     *
     * @param Doggy_Dispatcher_Request $request
     * @return Doggy_Dispatcher_Context
     */
    public function setRequest($request){
       $this->request = $request;
       return self::$context;
   }
   /**
    * 设置当前Response对象
    *
    * @param Doggy_Dispatcher_Response $response
    * @return Doggy_Dispatcher_Context
    */
   public function setResponse($response){
       $this->response = $response;
       return self::$context;
   }
   
   /**
    * 返回当前的SessionContext
    * 注意，这将自动初始化session
    * 
    * @return Doggy_Session_Context
    */
   public function getSessionContext(){
       return Doggy_Session_Context::getContext();
   }
   
   /**
    * 设置Session 数据
    * 
    * 这是一个快捷方法,实际为调用Doggy_Session_Context::set方法来设置session
    * 
    * @param string $key
    * @param mixed $value
    * @return Doggy_Dispatcher_Context
    * @see Doggy_Session_Context::set
    */
   public function setSession($key,$value){
       $this->getSessionContext()->set($key,$value);
       return $this;
   }
   /**
    * 返回Session数据
    * 
    * 这是一个快捷方法,实际为调用Doggy_Session_Context::get方法来来获取session中的数据
    * 
    * @param string $key
    * @return mixed
    * @see Doggy_Session_Context:get
    */
    public function getSession($key){
        return $this->getSessionContext()->get($key);
    }
    
    /**
     * session中是否有指定的数据
     * 这是一个快捷方法,实际为调用Doggy_Session_Context::has方法
     * 
     * @return boolean
     */
    public function hasSession($key){
        return $this->getSessionContext()->has($key);
    }
}
?>