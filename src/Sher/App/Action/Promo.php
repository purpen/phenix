<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_App_Action_Promo extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute', 'coupon');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->coupon();
	}
	
	/**
	 * 千万红包
	 */
	public function coupon(){
		$total_times = 3;
		
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		$redis = new Sher_Core_Cache_Redis();
		$times = (int)$redis->get($cache_key);
		
		$this->stash['left_times'] = $total_times - $times;
		
		return $this->to_html_page('page/tweleve.html');
	}
	
	/**
	 * 获取红包
	 */
	public function got_bonus(){
		$total_times = 3;
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		$redis = new Sher_Core_Cache_Redis();
		$times = $redis->get($cache_key);
		
		// 设置初始化次数
		if(!$times){
			$times = 0;
		}
		if($times >= $total_times){
			return $this->ajax_note('今天3次机会已用完，明天再来吧！', true);
		}
		
		// 获取红包
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop();
		
		// 获取为空，重新生产红包
		while(empty($result)){
			$bonus->create_batch_bonus(100);
			$result = $bonus->pop();
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}
		
		// 赠与红包
		$ok = $bonus->give_user($result['code'], $this->visitor->id);
		if($ok){
			$times += 1;
			$left_times = $total_times - $times;
			
			// 设置次数
			$redis->set($cache_key, $times++);
			
			$this->stash['left_times'] = $left_times;
		}
		
		$this->stash['bonus'] = $result;
		
		return $this->to_taconite_page('ajax/bonus_ok.html');
	}
	
}
?>