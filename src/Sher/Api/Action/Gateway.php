<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Gateway extends Sher_Core_Action_Authorize {
	public $stash = array(
		'page' => 1,
		'c' => '',
		'uid' => 0,
	);

	protected $exclude_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->to_raw('Hi Taihuoniao!');
	}
	
	/**
	 * 获取红包
	 */
	public function bonus(){
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop();
		
		# 获取为空，重新生产红包
		while (empty($result)){
			$bonus->create_batch_bonus(100);
			$result = $bonus->pop();
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}
		
		# 返回结果
		$data = array(
			'code' => $result['code'],
			'amount' => $result['amount'],
		);
		
		return $this->to_json(200, '', $data);
	}
	
	/**
	 * 更新红包
	 */
	public function up_bonus(){
		$code = $this->stash['c'];
		if (empty($code)){
			return $this->ajax_json('非法操作', true);
		}
		
		// 查看是否存在
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->find_by_code($code);
		
		if (empty($result)){
			return $this->ajax_json('此红包不存在或已被删除！', true);
		}
		
		// 解锁
		if ($result['status'] == Sher_Core_Model_Bonus::STATUS_PENDING){
			$id = (string)$result['_id'];
			$bonus->unpending($id);
		}
		
		return $this->ajax_json('操作成功！');
	}
	
	/**
	 * 领取红包
	 */
	public function got_bonus(){
		$code = $this->stash['c'];
		$user_id = $this->stash['uid'];
		if (empty($code) || empty($user_id)){
			return $this->ajax_json('非法操作', true);
		}
		
		// 查看是否存在
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->find_by_code($code);
		
		if (empty($result)){
			return $this->ajax_json('此红包不存在或已被删除！', true);
		}
		
		// 是否使用过
		if ($result['used'] == Sher_Core_Model_Bonus::USED_OK){
			return $this->ajax_json('红包已被使用！', true);
		}
		
		// 是否过期
		if ($result['expired_at'] && $result['expired_at'] < time()){
			return $this->ajax_json('红包已被过期！', true);
		}
		
		// 是否失效
		if ($result['status'] == Sher_Core_Model_Bonus::STATUS_DISABLED){
			return $this->ajax_json('红包已失效不能使用！', true);
		}
		
		$ok = $bonus->give_user($code, $user_id);
		
		return $this->ajax_json('操作成功！');
	}
	
}
?>