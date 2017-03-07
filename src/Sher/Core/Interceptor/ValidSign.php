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

          $current_user_id = 0;
          // 通过uuid获取当前用户ID
          $uuid = $request->get('uuid');
          $app_type = $request->get('app_type');
          $app_type = !empty($app_type) ? (int)$app_type : 1;
          if(!empty($uuid)){
            if($app_type==1){
              $pusher_model = new Sher_Core_Model_Pusher();
            }elseif($app_type==2){
              $pusher_model = new Sher_Core_Model_FiuPusher();          
            }else{
 		          return $action->api_json("应用来源不正确", 4007);
            }
            $pusher = $pusher_model->first(array('uuid'=> $uuid, 'is_login'=>1));
            if($pusher){
              $current_user_id = $pusher['user_id'];
            }
          }

          // 判断当前接口是否要求用户登录
          $check_result = $action->check_current_user($invocation->getMethod(), $current_user_id);
          if(!$check_result['success']){
		        return $action->api_json($check_result['message'], 4008);
          }

          $action->current_user_id = $current_user_id;
          $action->current_app_type = $app_type;

          // 判断是否验证签名(测试环境可不验证)
          $is_validate_sign = (int)Doggy_Config::$vars['app.api.is_validate_sign'];
          if(empty($is_validate_sign)){
            return $invocation->invoke();
          }

	        // 验证传递参数

			$stash = $action->stash;
			$client_id = isset($stash['client_id']) ? $stash['client_id'] : null;
			$sign = isset($stash['sign']) ? $stash['sign'] : null;

            if(empty($client_id)){
              return $this->mismatch_sign($action);
            }

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
   * 判断参数值是否都为空
   */
  public function judge_param(){
    
  }
	
	/**
	 * 生成签名
	 */
	public function get_signature($arrdata, $client_id){
		ksort($arrdata);
    // 不参与验签的字段名
    $ignore_data = array(
      'sign', 'tmp', 'avatar_tmp', 'id_card_a_tmp', 'business_card_tmp', 'link', 'cover_url', 'banners_url',
    );
		
		$paramstring = '';
		foreach($arrdata as $key => $value){
			// 空参数值不参与签名
			if(!isset($value)){
				continue;
      }
      //不参与签名的字段
      if(in_array($key, $ignore_data)){
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
    if(empty($paramstring)){
      return '';
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
		return $action->api_json('用户ID不存在！', 5000);
	}
	
	/**
	 * 签名验证不匹配
	 */
	public function mismatch_sign($action){
		return $action->api_json('请求签名验证错误,请重试---!', 4009);
	}
	
}

