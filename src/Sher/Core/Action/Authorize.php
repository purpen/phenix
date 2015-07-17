<?php
/**
 * 权限验证,方法执行清单过滤
 * @author purpen
 */
Class Sher_Core_Action_Authorize extends Sher_Core_Action_Base implements DoggyX_Action_VisitorAware {
	
	// 匿名用户函数清单
	protected $exclude_method_list = array();
	
	// 管理员函数清单
	protected $admin_method_list = array();
    
    // 系统管理员函数清单
    protected $system_method_list = array();
	
	/**
	 * 检查用户权限
	 */
	public function check_visitor($invoke_method, $login_user, &$handle) {

	    //匿名用户可执行方法
	    if ($this->exclude_method_list === '*' || in_array($invoke_method, $this->exclude_method_list)) {
	        return;
	    }
		
	    if (!$this->visitor->id) {
	        $handle = true;
	        return $this->custom_authorize_info_page();
	    }
		
	    // double check user state
	    if ($login_user->state != Sher_Core_Model_User::STATE_OK) {
	        $handle = true;
	        return $this->custom_authorize_info_page();
	    }

	    // short-circle for system adminstrator
	    if ($login_user->is_admin()) {
	        return;
	    }

	    if ($this->admin_method_list === '*' || in_array($invoke_method,$this->admin_method_list)) {
	        if (!$login_user->is_admin() && !$login_user->can_admin()) {
	            $handle = true;
	            return $this->deny();
	        }
	        return;
	    }

        if ($this->system_method_list === '*' || in_array($invoke_method,$this->system_method_list)) {
            if (!$login_user->is_system()) {
                $handle = true;
                return $this->custom_authorize_info_page();
            }
            return;
        }
	    // most login-user
		
	    return;
	}
	
	/**
	 * 拒绝权限
	 */
	protected function deny() {
        $this->stash['note'] = '抱歉，权限不足！';	
		return $this->to_html_page('page/note_page.html');
	}
	
	/**
	 * Override this to define custom login info page
	 * 重定向登陆信息页面
	 * @return string
	 */
	protected function custom_authorize_info_page() {
	    return $this->to_redirect(Doggy_Config::$vars['app.url.login']);
	}
	
}
?>
