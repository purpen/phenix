<?php
/**
 * This interceptor gets all parameters from {@link ActionContext#getParameters()} and sets them on the Action. Note that the parameter map must contain a String key and
 * often containers a string for the value.
 * As security , any parameter begin with '_' will not apply to action, in addition, if the action being invoked implements an acceptableName method,
 * the action will be consulted to determine if the parameter should be set.
 *
 * TODO:
 * 目前，如果Action有setXXX,XXX是parameter的name，则该值会被设置。例如如果有parameter名称为name,并且action中定义了setname或者setName方法，
 * 则会调用相应的方法。
 * 是否需要考虑直接$ac->XXX 来直接设置parameter,安全上漏洞是否很大?
 *
 * @author night
 *
 */
class Doggy_Dispatcher_Interceptor_Parameters extends Doggy_Dispatcher_Interceptor_Abstract  {
    /**
     * 主调用
     *
     * @param Doggy_Dispatcher_ActionInvocation $invocation
     */
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation){
        $action = $invocation->getAction();
        if ($action instanceof Doggy_Dispatcher_Action_Interface_NoParameters ) {
        	return $invocation->invoke();
        }
        Doggy_Util_Dispatcher::applyDispatcherContextParams($invocation->getInvocationContext(),$action);
        return $invocation->invoke();
    }
    /**
     * Detemine wheather a parameter name is acceptable to set
     *
     * @param string $name
     * @return boolean
     */
    protected function acceptableName($name){
        return !(substr($name,0,1) == '_');
    }
}
?>