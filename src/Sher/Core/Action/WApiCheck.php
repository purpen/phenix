<?php
/**
 * 权限验证,方法执行清单过滤
 * @author tianshuai
 */
Class Sher_Core_Action_WApiCheck extends Sher_Core_Action_Base implements Sher_Core_Action_WFunnel {
	
  /**
   * 不强制登录的方法(uid)
  */
  protected $filter_auth_methods = array();
	
	/**
	 * 检查用户是否登录
	 */
	public function verify_auth($invoke_method, $uid) {

        $result = array();
        $result['success'] = true;
        $result['message'] = null;
        //匿名用户可执行方法
        if (!($this->filter_auth_methods === '*' || in_array($invoke_method, $this->filter_auth_methods))) {
          if(empty($uid)){
            $result['success'] = false;
            $result['message'] = "请先登录!";
          }
        }
        return $result;
	}
	
}

