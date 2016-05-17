<?php
/**
 * 过滤ip黑名单
 * @author tianshuai
 */
class Sher_Core_Interceptor_Filter extends Doggy_Dispatcher_Interceptor_Abstract {
	/**
	 * 实现过程
	 */
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation) {
        $action  = $invocation->getAction();
        $request = $invocation->getInvocationContext()->getRequest();
        if ($action instanceof Sher_Core_Action_Filter) {
          // 判断IP是否合法
          $ip = Sher_Core_Helper_Auth::get_ip();
          $handle = false;
          $result = $action->check_current_ip($invocation->getMethod(), $ip, $handle);
          if($handle){
            return $result;
          }
        }
        return $invocation->invoke();
    }

}

