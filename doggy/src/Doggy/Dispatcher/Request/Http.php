<?php
/**
 * 支持HTTP特性的Request
 *
 * @version $Id:Http.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 */
class Doggy_Dispatcher_Request_Http extends Doggy_Dispatcher_Request_Abstract{

    protected $_pathInfo='';
    protected $_baseUrl='';
    protected $_isAjax=null;

    /**
     * __get 别名
     *
     * @param mixed $key
     * @return mixed
     */
    function get($key){
        return $this->__get($key);
    }
    /**
     * Magic method,获取当前Request中的变量的值,搜索顺序依次为:
     * Params,_GET,_POST,_COOKIE,_SERVER,_ENV
     *
     * @param string $key
     * @return mixed
     */
    function __get($key){
        switch (true) {
            case isset($this->_params[$key]):
                return $this->_params[$key];
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getRequestUri();
            case ($key == 'PATH_INFO'):
                return $this->getPathInfo();
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            default:
                return null;
        }
    }
    /**
     * 检验某个变量是否被设置,依次检查:
     * _params,_GET,_POST,_COOKIE,_SERVER,_ENV
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key){
        switch (true) {
            case isset($this->_params[$key]):
                return true;
            case isset($_GET[$key]):
                return true;
            case isset($_POST[$key]):
                return true;
            case isset($_COOKIE[$key]):
                return true;
            case isset($_SERVER[$key]):
                return true;
            case isset($_ENV[$key]):
                return true;
            default:
                return false;
        }
    }
    /**
     * 获得Cookie中指定key的变量值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getCookie($key,$default=NULL){
        return isset($_COOKIE[$key])?$_COOKIE[$key]:$default;
    }
    /**
     * 获取_SERVER变量中指定key的变量值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getServer($key,$default=NULL){
        return isset($_SERVER[$key])?$_SERVER[$key]:$default;
    }
    /**
     * 获取_POST变量中指定key的变量值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getPost($key,$default=null){
        return isset($_POST[$key])?$_POST[$key]:$default;
    }
    /**
     * 获取_GEt变量中指定key的变量值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getGet($key,$default=null){
        return isset($_GET[$key])?$_GET[$key]:$default;
    }
    /**
     * 当前Request的Method:PUT/GET/DELETE/POST
     *
     * @return string
     */
    public function getMethod(){
        return $_SERVER['REQUEST_METHOD'];
    }
    /**
     * Returns Path_Info
     *
     * @return string
     */
    public function getPathInfo(){
        if(empty($this->_pathInfo)){
            $this->setPathInfo();
        }
        return $this->_pathInfo;
    }
    /**
     * Set Path_Info,如果要设置的值为空则自动检测
     *
     *
     * @param string $pathInfo
     * @return string
     */
    public function setPathInfo($pathInfo = null){
        if($pathInfo===null){
            $pathInfo = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:NULL;
        }
        $this->_pathInfo = (string) $pathInfo;
        return $this;
    }
    /**
     * Set request base url,if value is null will guess current base url
     *
     * @param string $baseUrl
     * @return string
     */
    public function setBaseUrl($baseUrl = null){
        if ((null !== $baseUrl) && !is_string($baseUrl)) {
            return $this;
        }
        if ($baseUrl === null){
            $filename = basename($_SERVER['SCRIPT_FILENAME']);
            if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
                $baseUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } else {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path    = $_SERVER['PHP_SELF'];
                $segs    = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
                $segs    = array_reverse($segs);
                $index   = 0;
                $last    = count($segs);
                $baseUrl = '';
                do {
                    $seg     = $segs[$index];
                    $baseUrl = '/' . $seg . $baseUrl;
                    ++$index;
                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            // Does the baseUrl have anything in common with the request_uri?
            $requestUri = $this->getRequestUri();

            if (0 === strpos($requestUri, $baseUrl)) {
                // full $baseUrl matches
                $this->_baseUrl = $baseUrl;
                return $this;
            }
            if (0 === strpos($requestUri, dirname($baseUrl))) {
                // directory portion of $baseUrl matches
                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
                return $this;
            }
            if (!strpos($requestUri, basename($baseUrl))) {
                // no match whatsoever; set it blank
                $this->_baseUrl = '';
                return $this;
            }
            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            if ((strlen($requestUri) >= strlen($baseUrl))
                && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
            {
                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
            }
        }
        $this->_baseUrl = rtrim($baseUrl, '/');
        return $this;
    }
    public function getBaseUrl(){
        if(empty($this->_baseUrl)){
            $this->setBaseUrl();
        }
        return $this->_baseUrl;
    }
    /**
     * Returns REQUEST_URI
     *
     * @return string
     */
    public function getRequestUri(){
        return $_SERVER['REQUEST_URI'];
    }
    /**
     * 获得客户端的所有发送的HTTP Header
     *
     * 注：
     * 不能使用Apache的getallheader,我们需要支持FCGI
     *
     * @return array
     */
    static public function getHeaders(){
        $headers = array();
        foreach($_SERVER as $name => $value){
            if(substr($name, 0, 5) == 'HTTP_')
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;

        }
        return $headers;
    }
    /**
     * 获取HTTP名称的Header值
     *
     * <p>
     * $name符合标准的Http名称如Last_Modified,Accept-Encoding
     * </p>
     *
     * @param string $name
     * @return unknown
     */
    public function getHeader($name){
        $headers = self::getHeaders();
        $temp = ucwords($name);
        return isset($headers[$temp])?$headers[$temp]:null;
    }
    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost(){
        return 'POST' == $this->getMethod();
    }
    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut(){
        return 'PUT' == $this->getMethod();
    }
    /**
     * Was the request made by DELTE?
     *
     * @return boolean
     */
    public function isDelete(){
        return 'DELETE' == $this->getMethod();
    }
    /**
     * 判断是否为Ajax方式的请求
     *
     * why:
     * 有时候我们需要使用iframe方式来模拟xmlhttpd,因此需要除了_SERVER外,还应检查是否
     * 在POST/GET中有这个伪变量存在!
     *
     * @return boolean
     */
    public function isAjaxRequest(){
        if($this->_isAjax===null){
            $this->setIsAjaxRequest();
        }
	    return $this->_isAjax;
    }
    /**
     * 设定当前Request是否为一个Ajax的请求
     *
     * @param boolean $value
     * @return Doggy_Dispatcher_Request_Http
     */
    public function setIsAjaxRequest($value=null){
        if($value===null){
            $this->_isAjax = ($this->__isset('HTTP_X_REQUESTED_WITH'));
        }else{
            $this->_isAjax = $value;
        }
        return $this;
    }
    /**
     * 当前是否为安全SSL
     *
     * @return boolean
     */
    function isSecure(){
		if (isset($_SERVER['HTTPS'])) {
			return strtolower($_SERVER['HTTPS']) == 'on' ? TRUE : FALSE;
		}
		return FALSE;
	}
	/**
	 * merge params,_GET,_POST,_params will override _GET,_POST
	 *
	 * @return unknown
	 */
	public function getParams(){
        $return = $this->_params;
        if (isset($_GET) && is_array($_GET)) {
            $return += $_GET;
        }
        if (isset($_POST) && is_array($_POST)) {
            $return += $_POST;
        }
        return $return;
    }
    public function getParam($key, $default = null) {

        if (isset($this->_params[$key])) {
            return $this->_params[$key];
        } elseif ((isset($_GET[$key]))) {
            return $_GET[$key];
        } elseif ((isset($_POST[$key]))) {
            return $_POST[$key];
        }
        return $default;
    }
    public function setParams(array $params){
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
        return $this;
    }
    /**
     * 返回_FILES数组
     */
    public function getUploadFiles(){
        return $_FILES;
    }
    /**
     * 确认文件是否是通过PHP上传的文件
     * @param string $file
     * @return boolean
     */
    public function isUploadedFile($file){
        return is_uploaded_file($file);
    }
    /**
     * 返回客户端的ip地址
     */
    public function getClientIp(){
        //try find proxy ip instead of fuzzy remote_addr
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return $ip;
    }
    
    /**
     * get remote ip(REMOTE_ADDR)
     *
     * @return string
     */
    public function getRemoteIp(){
        if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return $ip;
    }
    
    /**
     * check if running lighttpd server
     *
     * @return boolean
     */
    public function isLightyServer(){
        return strpos($_SERVER['SERVER_SOFTWARE'],'lighttpd')!==false;
    }
    /**
     * check if running Nginx server
     *
     * @return boolean
     */
    public function isNginxServer(){
        return strpos($_SERVER['SERVER_SOFTWARE'],'nginx')!==false;
    }
}
?>