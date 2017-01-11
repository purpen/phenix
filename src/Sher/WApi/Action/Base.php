<?php
/**
 * WAPI模块基类
 * @author tianshuai
 */
class Sher_WApi_Action_Base extends Sher_Core_Action_WApiCheck{
	
	/**
	 * Token
	 */
	public $token;


    /**
     * 用户ID
     */
	public $uid;


	/**
	 * 初始化验证
	 */
	public function _init() {
		$this->token = isset($this->stash['token']) ? $this->stash['token'] : null;
		$this->uid = isset($this->uid) ? (int)$this->uid : 0;

		//当前方法名
		$this->current_method_name = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1);
    }
	
	/**
	 * 重建结果集
	 */
	public function rebuild_result($result, $some_fields){}
	
}

