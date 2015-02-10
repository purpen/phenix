<?php
/**
 * 验证签名拦截器,适用于App API调用验证
 * @author purpen
 */
class Sher_Core_Interceptor_ValidSign extends Doggy_Dispatcher_Interceptor_Abstract {
	/**
	 * 实现过程
	 */
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation) {
        $action  = $invocation->getAction();
        $request = $invocation->getInvocationContext()->getRequest();
        
        if ($action instanceof Sher_Core_Action_Funnel) {
	        // 验证传递参数
			$current_user_id = $request->get('current_user_id');
			if($current_user_id == 0){
				return $this->deny_anonymous($action);
			}
			
			$stash = $action->stash;
			$client_id = $action->client_id;
			$sign = $action->sign;
			print_r($stash);
			// 验证签名
			$valid_sign = $this->get_signature($stash, $client_id);
			Doggy_Log_Helper::warn("Request sign: $sign & valid sign: $valid_sign!");
			if($sign != $valid_sign){
				return $this->mismatch_sign($action);
			}
        }
		
        return $invocation->invoke();
    }
	
	/**
	 * 生成签名
	 */
	public function get_signature($arrdata, $client_id){
		ksort($arrdata);
		
		$paramstring = '';
		foreach($arrdata as $key => $value){
			// 空参数值不参与签名
			if(empty($value)){
				continue;
			}
			if(strlen($paramstring) == 0){
				$paramstring .= $key . "=" . $value;
			}else{
				$paramstring .= "&" . $key . "=" . $value;
			}
		}
		
		Doggy_Log_Helper::warn("Sign client_id: $client_id ");
		if($client_id == Doggy_Config::$vars['app.frbird.key']){
			$sercet = Doggy_Config::$vars['app.frbird.sercet'];
		}else{
			// 返回为空
			return;
		}
		Doggy_Log_Helper::warn("Sign params: $paramstring ");
		$sign = md5(md5($paramstring.$sercet.$client_id));
		Doggy_Log_Helper::warn("Sign: $sign ");
		return $sign;
	}
	
	/**
	 * 拒绝匿名用户
	 */
	public function deny_anonymous($action){
		return $action->api_json('用户ID不存在！', 3000);
	}
	
	/**
	 * 签名验证不匹配
	 */
	public function mismatch_sign($action){
		return $action->api_json('请求签名验证错误,请重试---!', 3000);
	}
	
}
?>