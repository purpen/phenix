<?php
/**
 * Response 抽象类
 *
 * @version $Id: Abstract.php 6369 2007-10-18 07:17:33Z night $
 * @author night
 * @package Doggy
 * @subpackage Dispatcher.Response
 */
abstract class Doggy_Dispatcher_Response_Abstract extends Doggy_Object {
    /**
     * 输出缓冲区
     * @var array
     */
    protected $_buffer = array();
    /**
     * Header数组. 每个数组为key=>value
     *
     * @var array
     */
    protected $_headers = array();
    /**
     * Array of raw headers. Each header is a single string, the entire header to emit
     * @var array
     */
    protected $_headersRaw = array();
    /**
     * Set a header
     *
     * If $replace is true, replaces any headers already defined with that
     * $name.
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function setHeader($name, $value, $replace = false){
        $name  = (string) $name;
        $value = (string) $value;
        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }
        $this->_headers[] = array(
            'name'  => $name,
            'value' => $value
        );
        return $this;
    }
    /**
     * 返回headers数组
     *
     * @return array
     */
    public function getHeaders(){
        return $this->_headers;
    }

    /**
     * 清除header
     *
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function clearHeaders(){
        $this->_headers = array();
        return $this;
    }

    /**
     * 设置RAW 头信息
     *
     *
     * @param string $value
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function setRawHeader($value){
        $this->_headersRaw[] = (string) $value;
        return $this;
    }

    /**
     * Retrieve all {@link setRawHeader() raw HTTP headers}
     *
     * @return array
     */
    public function getRawHeaders(){
        return $this->_headersRaw;
    }

    /**
     * Clear all {@link setRawHeader() raw HTTP headers}
     *
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function clearRawHeaders(){
        $this->_headersRaw = array();
        return $this;
    }

    /**
     * Clear all headers, normal and raw
     *
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function clearAllHeaders(){
        return $this->clearHeaders()->clearRawHeaders();
    }
    /**
     * Set output buffer content
     *
     *
     * @param string $content
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function setBuffer($content){
        $this->_buffer = array((string) $content);
        return $this;
    }
    /**
     * Append content to the output buffer
     *
     * @param string $content
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function appendBuffer($content){
        $this->_buffer[] = (string) $content;
        return $this;
    }
    /**
     * Return the body content
     * @return string|array
     */
    public function getBuffer($asArray = false){
        return $asArray?$this->_buffer:implode('',$this->_buffer);
    }
    abstract public function sendBuffer();
    abstract public function flushResponse();
    abstract public function sendHeaders();
}
?>