<?php
/**
 * 商品专题页面
 * @author tianshuai
 */
class Sher_Wap_Action_Mall extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	

	protected $exclude_method_list = array('execute', 'test', 'lunar','fitbit','gopro');

	
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
		return $this->to_html_page('wap/mall/lunar.html');
	}
	
	/**
	 * fitbit
	 */
	public function fitbit(){
		return $this->to_html_page('wap/mall/fitbit.html');
	}
	
	/**
	 * gopro
	 */
	public function gopro(){
		return $this->to_html_page('wap/mall/gopro.html');
	}
	
	/**
	 * 配奶机
	 */
	public function milk(){
		return $this->to_html_page('wap/mall/milk.html');
	}

  /**
   * test
   */
  public function test(){
    return $this->to_html_page('wap/test.html'); 
  }
	
}

