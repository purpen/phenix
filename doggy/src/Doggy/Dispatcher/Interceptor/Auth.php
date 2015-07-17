<?php
/**
 * Doggy_Dispatcher_Interceptor_Auth
 * 
 * 完成用户的身份验证和对受保护的资源的访问
 * 
 * 本拦截器需要和 Doggy_Auth包的类和接口进行偕同工作.
 * 
 * 
 */
class Doggy_Dispatcher_Interceptor_Auth extends Doggy_Dispatcher_Interceptor_Abstract {
    /**
     * Authentication Provider
     *
     * @var Doggy_Auth_Authentication_Provider
     */
    protected $_auth;
    /**
     * 授权失败需要重定向的Doggy_Auth_Authentication_Action
     * @var Doggy_Auth_Authentication_Action
     */
    private $_action;
    
    public function init(){
        $this->_auth = Doggy_Auth_Authentication_Manager::getProvider();
        
        $setting = Doggy_Config::get('app.dispatcher.interceptors.auth');
        
        $action_class = $setting['authen_action'];
        
        if (empty($action_class) || !class_exists($action_class)) {
            Doggy_Log_Helper::error("app.dispatcher.interceptors.auth:<authen_action> is NULL!",__METHOD__);
            throw new Doggy_Dispatcher_Exception('auth.authen_action not defined!');
        }
        
        if (!Doggy::is_implements($action_class,'Doggy_Auth_Authentication_Action')){
            Doggy_Log_Helper::error("app.dispatcher.interceptors.auth:< authen_action:$action_class > invalid!",__CLASS__);
            throw new Doggy_Dispatcher_Exception("app.dispatcher.interceptors.auth:< authen_action:$action_class > invalid!");
        }
        $action = new $action_class();
        $this->_action =$action;
    }
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation){
        $this->beforeAuth($invocation);
        $this->checkAuth($invocation);
        $this->afterAuth($invocation);
        return $invocation->invoke();
    }
    protected function beforeAuth(Doggy_Dispatcher_ActionInvocation $invocation){}
    protected function afterAuth(Doggy_Dispatcher_ActionInvocation $invocation){}
    private function checkAuth(Doggy_Dispatcher_ActionInvocation $invocation){
        $action = $invocation->getAction();
        
        if (!$action instanceof Doggy_Auth_Resource ) {
            Doggy_Log_Helper::debug("Action is not a Doggy_Auth_Resource,skip... ");
        	return;
        }
        $authentication = $this->_auth->createAuthentication();
        $action->_setAuthentication($authentication);
        if(!$action instanceof Doggy_Auth_AuthorizedResource){
            Doggy_Log_Helper::debug("Action is not a Doggy_Auth_AuthorizedResource,exit.... ");
            return;
        }
        
        
        $resourceId = $action->getResourceId();
        $method = strtolower($invocation->getMethod());
        //获得权限列表
        $privilegeMap = $action->getPrivilegeMap();
        if(!isset($privilegeMap[$method])){
            if(!isset($privilegeMap['*'])){
                Doggy_Log_Helper::debug("$method not defined in PrivilegeMap,skip..");
                return;
            }else{
                Doggy_Log_Helper::debug("$method not defined in PrivilegeMap,use default[*] instead..");
                $privilegeInfo = $privilegeMap['*'];
            }
        }else{
            $privilegeInfo = $privilegeMap[$method];
        }
        
        //首先检查特殊的权限名称
        $privilegeName = isset($privilegeInfo['privilege'])?$privilegeInfo['privilege']:$method;
        $uri = $invocation->getInvocationContext()->getRequest()->getRequestUri();
        
        switch ($privilegeName) {
            case Doggy_Auth_AuthorizedResource::PRIV_AUTHORIZED:
                if(!$authentication->isAuthenticated()){
                    Doggy_Log_Helper::debug("authentication not authorized,redirct to authorize action.");
                    $this->_action->_setNextUrl($uri);
                    $invocation->setAction($this->_action);
                    $invocation->setMethod('login');
                    return;
                }
                Doggy_Log_Helper::debug("Authorize by PRIV_AUTHORIZED,APPROVED.");
                return;
                break;
            case Doggy_Auth_AuthorizedResource::PRIV_CUSTOM:
                
                $customMethod = $privilegeInfo['custom'];
                if(!is_array($customMethod)){
                    $callback = array($action,$customMethod);
                }else{
                    $callback = $customMethod;
                }
                
                $ok = call_user_func_array($callback,array($authentication,$method));
                if($ok){
                    Doggy_Log_Helper::debug("Authorize by custom authorize,APPROVED.");
                    return;
                }
                Doggy_Log_Helper::debug("Authorize by custom authorize,DENNIED.");
                break;
            case Doggy_Auth_AuthorizedResource::PRIV_NONE:
                Doggy_Log_Helper::debug("Privilege is PRIV_NONE,SKIP.");
                return;
                break;
            default:
                if(!$authentication->isAuthenticated()){
                    Doggy_Log_Helper::debug("authentication not authorized,redirct to authorize action.");
                    $this->_action->_setNextUrl($uri);
                    $invocation->setAction($this->_action);
                    $invocation->setMethod('login');
                    return;
                }
                if($authentication->getAcl()->isAllowed($resourceId,$privilegeName)){
                    Doggy_Log_Helper::debug("Resource:[$resourceId] Privilege[$privilegeName] APPROVED.");
                    return;
                }
                Doggy_Log_Helper::debug("Resource:[$resourceId] Privilege[$privilegeName] DENNIED.");
                break;
        }
        //deny
        $invocation->setAction($this->_action);
        $invocation->setMethod('deny');
        return;
    }
}
/**vim:sw=4 et ts=4 **/
?>