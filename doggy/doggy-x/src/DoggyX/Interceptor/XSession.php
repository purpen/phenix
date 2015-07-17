<?php
/**
 * Load and inject visitor session into action stash
 */
class DoggyX_Interceptor_XSession extends Doggy_Dispatcher_Interceptor_Abstract {
    
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation) {
        $action = $invocation->getAction();
        if ($action instanceof DoggyX_Action_Stateless) {
            return $invocation->invoke();
        }
        $request = $invocation->getInvocationContext()->getRequest();
        $sid_key = isset(Doggy_Config::$vars['app.session.sid'])?Doggy_Config::$vars['app.session.sid']:Doggy_Config::$vars['app.id'].'_sid';
        $sid = $request->get($sid_key);
        
        $service = DoggyX_Session_Service::instance();
        
        $service->start_visitor_session($sid);
        $service->set_session_cookie();
        // delegate to check session authentication
        $this->check_session_auth($service,$invocation);
        //bind session to action
        $service->bind_session_to_action($action);
        
        if ($action instanceof DoggyX_Action_Initialize) {
            $action->_init();
        }
        
        if ($action instanceof DoggyX_Action_VisitorAware) {
            $handle = false;
            $forward_result = $action->check_visitor($invocation->getMethod(),$service->login_user,$handle);
            if ($handle) {
                return $forward_result;
            }
        }
        return $invocation->invoke();
    }
    
    protected function check_session_auth($service,$invocation) {
    }
}