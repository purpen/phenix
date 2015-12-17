<?php
/**
 * 权限验证,方法执行清单过滤
 * @author tianshuai
 */
Class Sher_Core_Action_ApiCheck extends Sher_Core_Action_Base implements Sher_Core_Action_Funnel {
	
  /**
   * 不强制登录的方法(current_user_id)
  */
  protected $filter_user_method_list = array();
	
	/**
	 * 检查用户权限
	 */
	public function check_current_user($invoke_method, $current_user_id) {

    $result = array();
    $result['success'] = true;
    $result['message'] = null;
    //匿名用户可执行方法
    if (!($this->filter_user_method_list === '*' || in_array($invoke_method, $this->filter_user_method_list))) {
      if(empty($current_user_id)){
        $result['success'] = false;
        $result['message'] = "请先登录!";
      }
    }
    return $result;
	}
	

	
}

