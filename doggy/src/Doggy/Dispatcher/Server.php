<?php
/**
 * Application Server
 *
 * Server is the frontend controller of whole doggy application.
 *
 * @package Doggy
 * @subpackage Dispatcher
 *
 * @version $Id:Server.php 6369 2007-10-18 07:17:33Z night $
 * @author Pan Fan<nightsailer@gmail.com>
 *
 */
final class Doggy_Dispatcher_Server {

    //~~~~
    protected  $filters;
    protected  $interceptors;
    protected  $modules;


    /**
     * ServerContext
     *
     * @var Doggy_Dispatcher_Context
     */
    private $context;

    private static $instance;

    protected  function __construct(){
    }

    /**
     * Execute filters,If any filter want to intercept 
     * current dispatch flow,then will return false.
     *
     * @param Doggy_Dispatcher_Request_Http $request
     * @param Doggy_Dispatcher_Response_Http $response
     * @param 
     * @return boolean 
     * @todo
     */
    protected function doFilters($request,$response,$time='before'){
        $filters = isset(Doggy_Config::$vars["app.dispatcher.filters.$time"][$time])? Doggy_Config::$vars["app.dispatcher.filters.$time"][$time] : array();
        $interrupt=false;
        if(!empty($filters)){
            foreach ($filters as $filterClass) {
                $filter = new $filterClass();
                if($filter->matches($request)){
                    if($time=='after'){
                        $filter->after($request,$response);
                    }else{
                        $interrupt = $filter->before($request,$response);
                    }
                }
            }
        }
        return $interrupt;
    }

    /**
     * Dispatch request to final Action
     *
     * @param Doggy_Dispatcher_Request_Http  $request
     * @param Doggy_Dispatcher_Response_Http $response
     *
     * @todo remove hard code
     */
    protected function serviceAction($request,$response){
        try{
            //run filter first
            if(!$this->doFilters($request,$response,'before')){
                $context = Doggy_Dispatcher_Context::getContext();
                $context->setRequest($request);
                $context->setResponse($response);
                $mapping = Doggy_Dispatcher_ActionMapper::parse($request);
                $request->setParams($mapping->getParams());
                $action = Doggy_Util_Inflector::camelize($mapping->getName());
                $method = $mapping->getMethod();
                $moduelNamespace = $mapping->getNamespace();
                $action_class = Doggy_Util_Inflector::doggyClassify($moduelNamespace.'_Action_'.$action);
                $invocation = new Doggy_Dispatcher_ActionInvocation($context,$action_class,$method);
                $invocation->invoke();
                $this->doFilters($request,$response,'after');
                $response->flushResponse();
            }
        }catch(Doggy_Dispatcher_Exception_IllegalRequest $e){
            $this->handleException('404 PAGE NOT FOUND',$e);
        }catch(Doggy_Dispatcher_Exception $e){
            $this->handleException('500 SERVER ERROR(DISPATCHER FAILED)',$e);
        }catch(Doggy_Exception $e){
            $this->handleException('500 SERVER ERROR(DOGGY UNKNOWN ERROR)',$e);
        }catch(Exception $e){
            $this->handleException('500 SERVER ERROR(UNKNOWN RUNTIME ERROR)',$e);
        }
    }
        
    /**
     * @param int $code
     * @param Exception $e
     */
    private function handleException($code,$e){
        Doggy_Log_Helper::error("Runtime Excpetion:".$e->getMessage(),__METHOD__);
        Doggy_Log_Helper::error($e->getTraceAsString(),__METHOD__);
        
        $errorPages=Doggy_Config::get('app.runtime.error_page');
        if(!empty($errorPages)){
            $default_page = isset($errorPages['default'])?$errorPages['default']:null;
            $page = isset($errorPages['error_'.$code])?$errorPages['error_'.$code]:$default_page;
        }
        if(empty($page)){
            if(Doggy_Config::$vars['app.mode']!='prod'){
                $this->halt('系统运行错误',$code,$e);    
            }else{
                @header("HTTP/1.1 $code");
                die($code);
            }
            
        }else{
            @include($page);
            exit;
        }
    }
    private function halt($msg,$code,$e=null){
        echo "<h3 style='color:red;border:1px solid #eee;padding:1em;'>$code</h3>";
       
        if(is_object($e) && $e instanceof Exception){
            $error_message =  $e->getTraceAsString();
            echo "<div>错误信息:<div style='border:1px solid #ccc;color:rgb(255, 157, 37);background-color:#fff;padding:1em;'>".$e->getMessage()."</div></div>";
            echo "<div style='margin-top:1em;border:1px dotted #eee;background-color:#f5fef5'><strong>回溯信息:</strong><br />";
            echo '<textarea style="background-color:#fff;color:green;border:1px solid;width:100%;" rows="30" readonly="true">'.$error_message.'</textarea>';
        }else{
            echo "<div>错误信息:<div style='border:1px solid #ccc;color:rgb(255, 157, 37);background-color:#fff;padding:1em;'>".$e."</div></div>";
        }
        echo "</div>";
        exit;

    }

    /**
     * Main  process
     *
     */
    protected function service(){
        $request = new Doggy_Dispatcher_Request_Http();
        $response = new Doggy_Dispatcher_Response_Http();
        $this->serviceAction($request,$response);
    }
    /**
     * Return current running server instance
     *
     * @return Doggy_Server
     */
    public static function run(){
        if(is_null(self::$instance)){
            self::$instance = new Doggy_Dispatcher_Server();
        }
        self::$instance->service();
    }
}
?>