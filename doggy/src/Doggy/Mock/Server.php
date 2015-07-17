<?php
/**
 * Mock server
 * 测试工具
 * 本类帮助你提供一个可测试的运行环境来，一般能够方便的测试action/interceptor的行为
 *
 */
class Doggy_Mock_Server {
    
    /**
     * @var Doggy_Mock_ActionInvocation
     */
    private $_invocation;
    private $_interceptors;
    private $_actionClass;
    private $_actionMethod;
    public function __construct($actionClass='Doggy_Mock_Action',$method="execute"){
        $this->_invocation = self::createMockAcitionInvocation($actionClass,$method);
        $this->_actionClass=$actionClass;
        $this->_actionMethod = $method;
        $this->resetAction($actionClass,$method);
    }
    /**
     * Re-install new action to invoke
     * @param string $actionClass
     * @param string $method 
     * @return Doggy_Mock_Server
     */
    public function resetAction($actionClass,$method){
        $action = new $actionClass();
        $this->_invocation->setAction($action);
        $this->_invocation->setMethod($method);
        $this->_actionClass = $actionClass;
        $this->_actionMethod = $method;
        return $this;
    }
    
    public function getAction(){
        return $this->_invocation->getAction();
    }
    
    public function getActionMethod(){
        return $this->_invocation->getMethod();
    }
    /**
     * @return Doggy_Mock_Request
     */
    public function getRequest(){
        return $this->_invocation->getInvocationContext()->getRequest();
    }
    /**
     * @return Doggy_Mock_Response
     */
    public function getReponse(){
        return $this->_invocation->getInvocationContext()->getResponse();
    }
    /**
     * 上传一个文件
     * @param string $name
     * @param string $file
     * @return Doggy_Mock_Server
     */
    public function upload($name,$file=null){
        $this->getRequest()->mockUpload($name,$file);
        return $this;
    }
    /**
     * Add a paramter  to context
     * 
     * @return Doggy_Mock_Server
     */
    public function addParam($name,$value){
        $this->getRequest()->setParam($name,$value);
        return $this;
    }
    /**
     * Add cookie into current context
     * 
     * @return Doggy_Mock_Server
     */
    public function addCookie($name,$value){
        $this->getRequest()->addCookie($name,$value);
        return $this;
    }
    /**
     * Add a http header into context
     * 
     * @return Doggy_Mock_Server
     */
    public function addHttpHeader($header,$value){
        $this->getRequest()->addHeader($header,$value);
        return $this;
    }
    /**
     * Set current request is or not an ajax request
     * 
     * @return Doggy_Mock_Server
     */
    public function setIsAjaxRequest($flag){
        $this->getRequest()->setIsAjaxRequest($flag);
    }
    /**
     * Invoke action
     */
    public function invoke(){
        return $this->_invocation->invoke();
    }
    /**
     * Invoke given interceptor in current context
     * 
     * @param mixed Interceptor object or its class
     */
    public function invokeInterceptor($interceptor){
        if(!is_object($interceptor)){
            $interceptor = new $interceptor();
        }
        if(!$interceptor instanceof Doggy_Dispatcher_Interceptor){
            throw new Doggy_Exception('invalid interceptor');
        }else{
            $interceptor->init();
        }
        return $interceptor->intercept($this->_invocation);
    }
    public function reset(){
        $this->_interceptors=array();
        $this->_invocation = self::createMockAcitionInvocation($this->_actionClass,$this->_actionMethod);
        $this->resetAction($this->_actionClass,$this->_actionMethod);
    }
    /**
     * 添加需要运行的interceptor
     * 
     * @param string $interceptor
     * @return Doggy_Mock_Server
     */
    public function addInterceptor($interceptor){
        $this->_interceptors[]= $interceptor;
        $this->_invocation->buildInteceptors($this->_interceptors);
        return $this;
    }
    /**
     * 构建一个mock action invocation对象，
     * 
     *
     * @return Doggy_Mock_ActionInvocation
     */
    public static function createMockAcitionInvocation($actionClass='Doggy_Mock_Action',$method="execute"){
        $context = Doggy_Dispatcher_Context::getContext();
        $request = new Doggy_Mock_Request();
        $response = new Doggy_Mock_Response();
        $context->setRequest($request);
        $context->setResponse($response);
        $invocation = new Doggy_Mock_ActionInvocation($context,$actionClass,$method);
        return $invocation;
    }
    /**
     * create a mock server for test
     * 
     * @return Doggy_Mock_Server
     */
    public static function mock($actionClass='Doggy_Mock_Action',$method="execute"){
        return new Doggy_Mock_Server($actionClass,$method);
    }
}
/**vim:sw=4 et ts=4 **/
?>