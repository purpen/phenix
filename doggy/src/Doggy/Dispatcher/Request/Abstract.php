<?php
/**
 * 抽象的Request类
 *
 * @version $Id: Abstract.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 */
abstract class Doggy_Dispatcher_Request_Abstract extends Doggy_Object {
    protected  $_params=array();
    /**
     * 获得当前Request的指定名称的参数值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key,$default=null){
        return isset($this->_params[$key])?$this->_params[$key]:$default;
    }
    /**
     * 设置当前Request的指定名称的参数值
     *
     * @param string $key
     * @param mixed $value
     * @return Doggy_Dispatcher_Request_Abstract
     */
    public function setParam($key,$value){
        $this->_params[$key]=$value;
        return $this;
    }
    /**
     * 获得Request的全部参数数组
     *
     * @return Array
     */
    public function getParams(){
        return $this->_params;
    }
    /**
     * 设置Request的参数数组
     *
     * @param array $params
     * @return Doggy_Dispatcher_Request_Abstract
     */
    public function setParams(Array $params){
        $this->_params = $this->_params + (array) $array;
        return $this;
    }
}
?>