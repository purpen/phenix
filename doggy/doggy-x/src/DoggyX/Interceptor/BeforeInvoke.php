<?php
/**
 * AOP before-invoke intercetpor
 */
class DoggyX_Interceptor_BeforeInvoke extends Doggy_Dispatcher_Interceptor_Abstract {
    
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation) {
        $action = $invocation->getAction();
        if ($action instanceof DoggyX_Action_BeforeInvoke) {
            $method = $invocation->getMethod();
            $handle = $action->before_invoke($method,$invocation);
            if ($handle) {
                return $handle;
            }
        }
        return $invocation->invoke();
    }
}