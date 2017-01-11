<?php
/**
 * 验证Token拦截器,适用于App WAPI调用验证(微信小程序)
 * @author tianshuai
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
                $service = Sher_Core_Session_Token::getInstance();
                $auth_token = $service->fetch_token($token);
                if($auth_token){
                    $token = $auth_token['token'];
                    $uid = $auth_token['user_id'];

                    // 更新token有效期
                    $service->touch_auth_token((string)$auth_token['_id']);
                }
            }

            // 判断当前接口方法是否要求用户登录
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

