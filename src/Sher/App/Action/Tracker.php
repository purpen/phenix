<?php
/**
 * 跟踪统计
 * @author purpen
 */
class Sher_App_Action_Tracker extends Sher_App_Action_Base {
	public $stash = array(
		'kid'=>'',
		'ref'=>'',
	);
	
	protected $exclude_method_list = array('execute');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		$id = $this->stash['kid'];
		
		$next_url = Doggy_Config::$vars['app.url.domain'];
		
		if(empty($id)){
			return $this->to_redirect($next_url);
		}
		
		$model = new Sher_Core_Model_Advertise();
		$adv = $model->load((int)$id);
		if(empty($adv) || empty($adv['web_url'])){
			return $this->to_redirect($next_url);
		}
		
		// 增加点击数
		$model->inc((int)$id, 'click_count', 1);
		
		$next_url = $adv['web_url'];
		
		return $this->to_redirect($next_url);
	}
	
}
?>