<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Gateway extends Sher_Core_Action_Authorize {
	public $stash = array(
		'page' => 1,
		'size' => 10,
		'uid' => 0,
		'c' => '',
		's' => '',
		'bonus' => '',
	);

	protected $exclude_method_list = array('execute', 'slide', 'bonus', 'up_bonus', 'game_result');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->to_raw('Hi Taihuoniao!');
	}
	
	/**
	 * 广告位轮换图
	 */
	public function slide(){
		$result = array();
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		// 请求参数
		$space_id = isset($this->stash['space_id']) ? $this->stash['space_id'] : 0;
		$name = isset($this->stash['name']) ? $this->stash['name'] : '';
		if(empty($name) && empty($space_id)){
			return $this->api_json('请求参数不足', 3000);
		}
		
		// 获取某位置的推荐内容
		if(!empty($name) && empty($space_id)){
			$model = new Sher_Core_Model_Space();
			$row = $model->first(array('name' => $name));
			if(!empty($row)){
				$space_id = (int)$row['_id'];
			}else{
				return $this->api_json('请求参数不足', 3002);
			}
		}
		
		$query   = array();
		$options = array();
		
		// 查询条件
		if ($space_id) {
			$query['space_id'] = (int)$space_id;
		}
		
		$query['state'] = Sher_Core_Model_Advertise::STATE_PUBLISHED;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
        $service = Sher_Core_Service_Advertise::instance();
        $result = $service->get_ad_list($query,$options);
		
		// 获取单条记录
		if($size == 1 && !empty($result['rows'])){
			$result = $result['rows'][0];
		}
		
		return $this->api_json('请求成功', 0, $result);
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
		
		return $this->ajax_json(200, false, null,$data);
	}
	
	/**
	 * 更新红包
	 */
	public function up_bonus(){
		$code = $this->stash['c'];
		$state = $this->stash['s']; // <0:未中,1:击中>
		if (empty($code) || !isset($state)) {
			return $this->ajax_json('缺少更新参数', true);
		}
		
		// 查看是否存在
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->find_by_code($code);
		
		if (empty($result)) {
			return $this->ajax_json('此红包不存在或已被删除！', true);
		}
		
		$id = (string)$result['_id'];
		$state = (int)$state;
		
		// 击中红包开始锁定
		if ($state == 1) {
			$bonus->locked($id);
		} else if ($state == 0) {
			// 未击中红包，进行释放
			if ($result['status'] == Sher_Core_Model_Bonus::STATUS_PENDING){
				$bonus->unpending($id);
			}
		} else {
			return $this->ajax_json('未知红包状态！', true);
		}
		
		return $this->ajax_json('操作成功！');
	}
	
	/**
	 * 领取红包
	 */
	public function got_bonus(){
		$bonus = $this->stash['bonus'];
		$user_id = $this->stash['uid'];
		if (empty($bonus) || empty($user_id)){
			return $this->ajax_json('领取失败：缺少请求参数！', true);
		}
		
		$bonus_list = preg_split('/[:]+/u', $bonus);
		if(empty($bonus_list)){
			return $this->ajax_json('领取失败：未获取红包信息！', true);
		}
		
		for($i=0; $i<count($bonus_list); $i++){
			$code = $bonus_list[$i];
			
			// 查看是否存在
			$model = new Sher_Core_Model_Bonus();
			$result = $model->find_by_code($code);
			
			if (empty($result)){
				Doggy_Log_Helper::warn('领取失败：红包不存在或已被删除！');
				continue;
			}
			
			// 是否使用过
			if ($result['used'] == Sher_Core_Model_Bonus::USED_OK){
				Doggy_Log_Helper::warn('领取失败：红包已被使用！');
				continue;
			}
			
			// 是否过期
			if ($result['expired_at'] && $result['expired_at'] < time()){
				Doggy_Log_Helper::warn('领取失败：红包已被过期！');
				continue;
			}
		
			// 是否失效
			if ($result['status'] == Sher_Core_Model_Bonus::STATUS_DISABLED){
				Doggy_Log_Helper::warn('领取失败：红包已失效不能使用！');
				continue;
			}
			
			$ok = $model->give_user($code, $user_id);
		}
		
		return $this->ajax_json('领取成功！');
	}
	
	/**
	 * 更新游戏结果
	 */
	public function game_result(){
		$uid = $this->stash['uid'];
		$result = $this->stash['bonus'];
		if(empty($result)){
			return $this->ajax_json('提交失败：缺少更新参数', true);
		}
		
		$bonus_list = preg_split('/[;]+/u', $result);
		if(empty($bonus_list)){
			return $this->ajax_json('提交失败：未获取红包信息', true);
		}
		
		for($i=0; $i<count($bonus_list); $i++){
			$bonus = preg_split('/[:]+/u', $bonus_list[$i]);
			$code = $bonus[0];
			$state = (int)$bonus[1];
			
			if($state != 0 && $state != 1){
				Doggy_Log_Helper::warn("Bonus state [$state] error!");
				continue;
			}
			
			// 查看是否存在
			$model = new Sher_Core_Model_Bonus();
			$row = $model->find_by_code($code);
			if(empty($row)){
				Doggy_Log_Helper::warn("Bonus [$code] not exist!");
				continue;
			}
		
			$id = (string)$row['_id'];
			// 击中红包开始锁定
			if($state == 1){
				$ok = $model->locked($id);
			}else{
				// 未击中红包，进行释放
				if($row['status'] == Sher_Core_Model_Bonus::STATUS_PENDING){
					$ok = $model->unpending($id);
				}
			}
		}
		
		return $this->ajax_json('提交成功！');
	}
	
}
?>