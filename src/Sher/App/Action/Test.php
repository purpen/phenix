<?php
/**
 * 测试中心
 * @author purpen
 */
class Sher_App_Action_Test extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->qiniu();
	}
	
	/**
	 * Qiniu
	 */
	public function qiniu(){
		$key = Doggy_Config::$vars['app.qiniu.key'];
		$secret = Doggy_Config::$vars['app.qiniu.secret'];
		$bucket = Doggy_Config::$vars['app.qiniu.bucket'];
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $key,
		    'secret_key' => $secret,
		    'bucket'     => $bucket
		));

		// 查看文件状态
		$res = $client->stat('test26508.jpg');
		
		print_r($res);
		
		return $this->to_html_page('page/test/qiniu.html');
	}
	
	
}
?>