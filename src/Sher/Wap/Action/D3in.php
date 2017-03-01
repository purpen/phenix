<?php
/**
 * D3IN铟立方未来实验室
 * @author purpen
 */
class Sher_Wap_Action_D3in extends Sher_Wap_Action_Base {

	public $stash = array(
		'page'=>1,
	    'size'=>6,
	);
	
	protected $exclude_method_list = array('execute','about');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->about();
	}
	
	/**
	 * about
	 */
	public function about(){
        return to_redirect('https://m.taihuoniao.com/fiu');
		$vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
	  $this->stash['vip_money'] = $vip_money;
		return $this->to_html_page('wap/d3in/about.html');
	}
	
}
