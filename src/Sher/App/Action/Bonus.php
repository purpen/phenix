<?php
/**
 * 红包页面
 * @author purpen
 */
class Sher_App_Action_Bonus extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute');
	
	/**
	 * 网站入口
	 */
	public function execute(){
	}
	
	
	/**
	 * 获取红包--20yuan
	 */
	public function valentine(){
    $user_id = $this->visitor->id;
		// 验证是否领取
		$cache_key = sprintf('valentine_20_%d', $user_id);
		$redis = new Sher_Core_Cache_Redis();
		$is_got = $redis->get($cache_key);
		
		//判断是否领取
		if($is_got){
			return $this->ajax_note('您已领完！', true);
		}
		
		// 获取红包
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop('VA');
		
		// 获取为空，重新生产红包
		while(empty($result)){
      //指定生成xname为VA, 20元红包
			$bonus->create_batch_bonus(30, 'VA', 'C');
			$result = $bonus->pop('VA');
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}
		
		// 赠与红包
		$ok = $bonus->give_user($result['code'], $user_id);
		if($ok){
			// 写入缓存
			$redis->set($cache_key, 1);
			
		}
		
		$this->stash['bonus'] = $result;
		
		return $this->to_taconite_page('page/bonus/valentine.html');
	}

}
?>
