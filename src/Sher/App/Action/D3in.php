<?php
/**
 * D3IN铟立方未来实验室
 * @author purpen
 */
class Sher_App_Action_D3in extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute', 'coupon', 'active','tool','member','yuyue','choose','ok');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->d3in();
	}
	
	/**
	 * d3in
	 */
	public function d3in(){
		return $this->to_html_page('page/d3in/index.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function active(){
    $this->set_target_css_state('sub_active');
		return $this->to_html_page('page/d3in/active.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function tool(){
    $this->set_target_css_state('sub_device');
		return $this->to_html_page('page/d3in/tool.html');
	}
	
	/**
	 * d3in 会员
	 */
	public function member(){
    $this->set_target_css_state('sub_member');
		return $this->to_html_page('page/d3in/member.html');
	}
	
	/**
	 * d3in volunteer
	 */
	public function volunteer(){
		return $this->to_html_page('page/d3in/volunteer.html');
	}
	
	/**
	 * d3in 预约
	 */
	public function yuyue(){
		return $this->to_html_page('page/d3in/yuyue.html');
	}
	
	/**
	 * d3in 预约2
	 */
	public function choose(){
		return $this->to_html_page('page/d3in/choose.html');
	}
	
	/**
	 * d3in 预约成功
	 */
	public function ok(){
		return $this->to_html_page('page/d3in/ok.html');
	}
	
}
?>
