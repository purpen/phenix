<?php
/**
 * 商品专题页面
 * @author tianshuai
 */
class Sher_App_Action_Mall extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute','lunar','fitbit','gopro','tlunar','tfitbit','tgopro');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->lunar();
	}
	
	/**
	 * lunar 祝眠灯
	 */
	public function lunar(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/lunar.html');
	}
	
	/**
	 * lunar 图集
	 */
	public function tlunar(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/tlunar.html');
	}
	
	/**
	 * fitbit
	 */
	public function fitbit(){
		$this->set_target_css_state('page_shop');
    //加载百度在线客服交流
    $this->stash['baidu_talk_server'] = true;
    $this->stash['server_code'] = '1dbd78eabac6f4dcc9e73ac23e0792ab';
		return $this->to_html_page('page/mall/fitbit.html');
	}
	/**
	 * fitbit 图集
	 */
	public function tfitbit(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/tfitbit.html');
	}
	
	/**
	 * GoPro
	 */
	public function gopro(){
		$this->set_target_css_state('page_shop');
    //加载百度在线客服交流
    $this->stash['baidu_talk_server'] = true;
    $this->stash['server_code'] = '7b69d6f73a00457e5b1d5824df47a5c1';
		return $this->to_html_page('page/mall/gopro.html');
	}
	
	/**
	 * GoPro 图集
	 */
	public function tgopro(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/tgopro.html');
	}

}

