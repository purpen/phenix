<?php
/**
 * 支持HTTP特性的Response
 *
 * @version $Id: Http.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 * @package Doggy
 * @subpackage Dispatcher.Response
 */
class Doggy_Dispatcher_Response_Http extends Doggy_Dispatcher_Response_Abstract {

    protected $contentType = null;
    protected $tag;
    protected $characterEncoding=null;
    protected $lastModified;
    /**
     * 回应代码
     *
     * @var int
     */
    protected $_httpResponseCode = 200;

    /**
     * Get response's charset
     *
     * @return unknown
     */
    public function getCharacterEncoding(){
		return $this->characterEncoding;
	}
	/**
	 * Set character encoding
	 *
	 * @param string $encoding
	 * @return Doggy_Dispatcher_Response_Http
	 */
	public function setCharacterEncoding($encoding){
	    $this->characterEncoding=$encoding;
	    return $this;
	}
	/**
	 * Get current response's content type
	 *
	 * @return string
	 */
	public function getContentType(){
	 	if(is_null($this->contentType)){
	 		return null;
	 	} else {
	 		$contentType = $this->contentType;
	 		if(!is_null($this->characterEncoding)){
	 			$contentType .= '; charset=' . $this->getCharacterEncoding();
	 		}
	 		return $contentType;
	 	}
	 }
	 /**
	  * 设置Response的ContentType
	  *
	  * @param string $type
	  * @return Doggy_Dispatcher_Response_Http
	  */
	 public function setContentType($type){
	     $this->contentType = $type;
	     return $this;
	 }
	 /**
	  * 设置最后修改时间戳
	  *
	  * @param int $date
	  * @return Doggy_Dispatcher_Response_Http
	  */
	 public function setLastModified($date){
	     $this->lastModified = $date;
	     return $this;
	 }
	 /**
	  * 获得最后修改的时间戳
	  *
	  * @return unknown
	  */
	 function getLastModifed(){
	     return $this->lastModified;
	 }
	 /**
     * 设置重定向的url
     *
     * @param string $url
     * @param int $code
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function setRedirect($url, $code = 302){
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);
        return $this;
    }
    /**
     * Set HTTP response code to use with headers
     *
     * @param int $code
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function setHttpResponseCode($code){
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new Doggy_Dispatcher_Response_Exception('Invalid HTTP response code');
        }
        $this->_httpResponseCode = $code;
        return $this;
    }

    /**
     * Retrieve HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode(){
        return $this->_httpResponseCode;
    }
    /**
     * Send all headers
     *
     *
     * @return Doggy_Dispatcher_Response_Abstract
     */
    public function sendHeaders(){
        if (!headers_sent()) {
            $httpCodeSent = false;
            $content_type = $this->getContentType();
            if(!empty($content_type)){
                $this->setHeader('Content-Type',$content_type,true);
            }
            foreach ($this->_headersRaw as $header) {
                if (!$httpCodeSent && $this->_httpResponseCode) {
                    header($header, true, $this->_httpResponseCode);
                    $httpCodeSent = true;
                } else {
                    header($header);
                }
            }
            foreach ($this->_headers as $header) {
                if (!$httpCodeSent && $this->_httpResponseCode) {
                    header($header['name'] . ': ' . $header['value'], false, $this->_httpResponseCode);
                    $httpCodeSent = true;
                } else {
                    header($header['name'] . ': ' . $header['value'], false);
                }
            }
            @header('X-Powered-By: Doggy/'.DOGGY_VERSION,true);
        }
        return $this;
    }
    /**
     * Send the response, including all headers
     *
     * @return void
     */
    public function flushResponse(){
        $this->sendHeaders();
        $this->sendBuffer();
    }
    /**
     * Output buffer
     *
     * @return void
     */
    public function sendBuffer(){
        echo @implode('',$this->_buffer);
    }
    public function __toString(){
        ob_start();
        $this->flushResponse();
        return ob_get_clean();
    }
    
    public function set_no_cache() {
        $this->setRawHeader('Expires: Sun, 1 Jan 2006 01:00:00 GMT');
        // $this->setRawHeader('Cache-Control: no-store, no-cache, must-revalidate,pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
        $this->setRawHeader('Cache-Control: must-revalidate, no-cache, private'); // HTTP/1.1
		$this->setRawHeader('Pragma: no-cache'); // HTTP/1.0
    }
}
?>