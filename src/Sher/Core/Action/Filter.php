<?php
/**
 * 入口过滤器(IP黑名单、)
 * @author tianshuai.
 */
interface Sher_Core_Action_Filter {

	/**
	 * 检查IP地址
	 */
	public function check_current_ip($invoke_method, $ip, &$handle);


}

