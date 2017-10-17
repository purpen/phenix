<?php
/**
 * An ActionInvocation represents the execution state of an Action. It holds the Interceptors and the Action instance.
 * By repeated re-entrant execution of the invoke() method, initially by the ActionProxy, then by the Interceptors, the
 * Interceptors are all executed, and then the Action and the Result.
 *
 * @package doggy
 * @subpackage Dispatcher
 */
class Doggy_Dispatcher_ActionInvocation  {

    protected $proxy;
    protected $invocationContext;
    /**
     * Interceptors inteceptor
     *
     * @var ArrayInterceptor
     */
    protected $interceptors;
    /**
     * Listener classes registed
     *
     * @var Array
     */
    protected $preResultListeners=array();

    /**
     * Result
     *
     * @var Doggy_Dispatcher_Result
     */
    protected $result;
    protected $resultCode;
    protected $executeResult=true;

    protected $executed=false;
    protected $action=null;



    private $action_class;
    private $method;
    
    function __construct(Doggy_Dispatcher_Context $context,$action,$method){
        $this->invocationContext = $context;
        $this->method = $method;
        $this->action_class = $action;
        $this->init();
    }
    /**
     * Initialize.
     * It will create the delegated Action
     */
    private function init(){
        //interceptors
        $list = Doggy_Config::get('app.dispatcher.interceptors.run',array());
        if(is_null($list) || !is_array($list)){
            $list = array();
        }
        $this->buildInteceptors($list);
    }
    protected function buildInteceptors($classes){
        if(is_array($classes)) $classes = (Array)$classes;
        $result = array();
        for($i=0;$i<count($classes);$i++){
            $cls=$classes[$i];
            if(!class_exists($cls,true)){
                Doggy_Log_Helper::warn("invalid interceptor,class:$cls");
                continue;
            }
            $itx = new $cls();
            if (!$itx  instanceof Doggy_Dispatcher_Interceptor ) {
                Doggy_Log_Helper::warn("invalid interceptor,class:$cls");
                continue;
            }
            $itx->init();
            $result[] = $itx;
        }
       $itx= new ArrayObject($result);
       $this->interceptors = $itx->getIterator();
       $this->interceptors->rewind();


    }
    protected function buildResultListeners($classes){
        if(is_array($classes)) $classes = (Array)$classes;
        $result = array();
        for($i=0;$i<count($classes);$i++){
            $cls=$classes[$i];
            try{
                $itx = new $cls();
                $result[] = $itx;
            }catch (Exception $e){
            }
        }
        $itx = new ArrayObject($result);
        $this->preResultListeners = $itx->getIterator();
        $this->preResultListeners->rewind();
    }

    /**
     * Exeucte result
     *
     */
    private function executeResult(){
        $result = $this->getResult();
        if($result == null){
            $code = $this->resultCode;
            $result_list = Doggy_Config::$vars['app.dispatcher.result.map'];
            if (!isset($result_list[$code])) {
                Doggy_Log_Helper::warn('result code:'.$code.' not defined,skip');
                return;
            }
            
            $result_class = $result_list[$code];
            
            if(is_null($result_class)|| !class_exists($result_class) || !Doggy::is_implements($result_class,'Doggy_Dispatcher_Result')){
                throw  new Doggy_Dispatcher_Exception("result code < $code > isn't map to any valid result class");
            }
            
            $this->result = new $result_class();
            
        }
        $this->result->execute($this);
    }

    /**
     * Create the Action to execute.
     *
     */
    protected function createAction(){
        if(class_exists($this->action_class)){
            $this->action = new $this->action_class();
        }else{
            throw new Doggy_Dispatcher_Exception_IllegalRequest('Illegal action:class '.$this->action_class.' cannot found!');
        }
        if(!$this->action instanceof Doggy_Dispatcher_Action){
            throw new Doggy_Dispatcher_Exception_IllegalRequest('Illegal action:class '.$action_class.' is not implements Action!');
        }
    
        if(empty($this->method) || !method_exists($this->action,$this->method)){
            if (empty($this->method)) {
                $method = 'execute';
            }
            else {
                $method = Doggy_Util_Inflector::methodlize($this->method);
                if (!method_exists($this->action,$method)) {
                    $method = 'execute';
                }
            }
            $this->method = $method;
        }
    }
    /**
     * 运行Action
     *
     *
     * 首先会在Action之前/或之后运行Inteceptor(如果定义),然后检查是否有pre_result_listener,
     * 有则运行,最后运行Result::execute,返回控制权
     *
     *
     *
     * @return string
     */
    public function invoke(){
        if($this->executed){
            throw new Doggy_Dispatcher_Exception('Illegal state,Action has been executed!');
        }
        //first run all inteceptors
        if($this->interceptors->valid()){
            $itx = $this->interceptors->current();
            $this->interceptors->next();
            $this->resultCode = $itx->intercept($this);
        }else{
            //已经执行完全部的inteceptor,准备运行action本身
            $this->resultCode = $this->invokeActionOnly();
        }
        //inteceptor递归的出口点
        if(!$this->executed){
            //回调注册的pre result listener
            for($i=0;$i<count($this->preResultListeners);$i++){
                $listen= $this->preResultListeners[$i];
                $listen->beforeResult($this,$this->resultCode);
            }
            if($this->getExecuteResult()){
                $this->executeResult();
            }
            $this->executed=true;
        }
        return $this->resultCode;
    }
    /**
     * Invoke an action only
     *
     * @return string
     */
    public function invokeActionOnly(){
        $action = $this->getAction();
        $method = $this->method;
        return $action->$method();
    }
    /**
     * Returns status of this action has been executed.
     *
     * @return unknown
     */
    public function isExecuted(){
        return $this->executed;
    }

    /**
     * Returns ActionContext
     *
     * @return Doggy_Dispatcher_Context
     */
    public function getInvocationContext(){
        return $this->invocationContext;
    }

    /**
     * Returns Action object
     *
     * @return Action
     */
    public function getAction(){
        if(is_null($this->action)){
            $this->createAction();
        }
        return $this->action;
    }
    /**
     * Inject an action object
     *
     * @param Doggy_Dispatcher_Action $action
     * @return Doggy_Dispatcher_DefaultActionInvocation
     */
    public function setAction(Doggy_Dispatcher_Action $action){
        $this->action = $action;
    }
    public function getResult(){
        return $this->result;
    }

    public function getMethod(){
        return $this->method;
    }
    public function setMethod($method){
        $this->method = $method;
        return $this;
    }

    public function addPreResultListener(Doggy_Dispatcher_PreResultListener $listener){
        $this->preResultListeners[] = $listener;
    }
    /**
     * 当前ActionInvocation返回的resultcode
     *
     * @return string
     */
    public function getResultCode(){
        return $this->resultCode;
    }

    public function setResultCode($code){
        $this->resultCode = $code;
    }
    public function getExecuteResult(){
        return $this->executeResult;
    }
    public function setExecuteResult($value){
        $this->executeResult = $value;
    }
}
?>
