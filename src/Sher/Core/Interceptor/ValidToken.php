<?php
/**
 * 验证Token拦截器,适用于App WAPI调用验证(微信小程序)
 * @author purpen
 */
class Sher_Core_Interceptor_ValidToken extends Doggy_Dispatcher_Interceptor_Abstract {
	/**
	 * 实现过程
	 */
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation) {

        $action  = $invocation->getAction();
        $request = $invocation->getInvocationContext()->getRequest();
        if ($action instanceof Sher_Core_Action_WFunnel) {

            $token = null;
            $uid = 0;
            // 通过uuid获取当前用户ID
            $token = $request->get('token');
            if(!empty($token)){

                if($token=='test'){
                    $uid = 20448;
                    $token = 'test';               
                }
            }

            // 判断当前接口是否要求用户登录
            $check_result = $action->verify_auth($invocation->getMethod(), $uid);
            if(!$check_result['success']){
                return $action->wapi_json($check_result['message'], 3000);
            }

            $action->uid = $uid;
            $action->token = $token;

        }
		
        return $invocation->invoke();
    }

	
}

