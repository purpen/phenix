<?php
/**
 * ComposeModelIntercetpor
 *
 * This interceptors will try auto compose Model object for invoked
 * action when matches the criteria:
 * 1. Action has a componseModel method and returns a valid instantial model
 * object.
 *
 * Interceptor will scan all parameters and fills them to model object.
 *
 * LIMIT: Currently, only supoort to componse one model object.
 *
 * @package core
 * @subpackage interceptors
 * @author night
 *
 */
class Doggy_Dispatcher_Interceptor_ModelDriven extends Doggy_Dispatcher_Interceptor_Abstract {
    /**
     * Called before the invocation has been executed.
     *
     * @param Doggy_Dispatcher_ActionInvocation $invocation
     *
     */
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation){

        Doggy_Log_Helper::debug('Componse Model starting...',__METHOD__);
        $action = $invocation->getAction();
        if(!$action instanceof Doggy_Dispatcher_Action_Interface_ModelDriven){
            Doggy_Log_Helper::debug('Action hasnot method:wiredModel,no anything to do,skip.',__METHOD__);
            return $invocation->invoke();
        }
        Doggy_Log_Helper::debug('Autowired model..',__METHOD__);
        $model = $action->wiredModel();
        if($model===false || is_null($model)){
            self::debug('Action disabled autowired Or returns a NULL composed model,skip.',__METHOD__);
            return $invocation->invoke();
        }
        Doggy_Log_Helper::debug('Componse Model DONE.',__METHOD__);
        Doggy_Util_Dispatcher::applyDispatcherContextParams($invocation->getInvocationContext(),$model);
        return $invocation->invoke();
    }
}
?>