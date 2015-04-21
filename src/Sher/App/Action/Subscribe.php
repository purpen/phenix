<?php
/**
 * 邮件订阅服务
 * @author purpen
 */
class Sher_App_Action_Subscribe extends Sher_App_Action_Base {
	public $stash = array(
		'email' =>'',
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->subscribe();
	}
	
	/**
	 * 邮件订阅
	 */
	public function subscribe(){
		$email = $this->stash['email'];
		
		if (empty($email)){
			return $this->ajax_json('请输入你的邮件地址', true);
		}
		
		try {
			$model = new Sher_Core_Model_Emailing();
			
			$ok = $model->apply_and_save(array('email' => $email));
			
		}catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("邮件订阅失败：".$e->getMessage());
			return $this->ajax_json("提交失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json('提交成功，感谢你的支持！', false);
	}
	
}
?>