<?php
/**
 * App模块基类
 * @author purpen
 */
class Sher_App_Action_Base extends Sher_Core_Action_Authorize {

	protected $dt_view_tags= array('sher_app');
	
	// 加载egou存在cookie中的值
	public function __construct(){
		//parent::__construct();
		if(isset($_COOKIE['egou_uid'])){
			$this->stash['egou_uid'] = $_COOKIE['egou_uid'];
			$this->stash['egou_hid'] = $_COOKIE['egou_hid'];
		}
	}
	
    /* 页面标示,用于前台css高亮,模板逻辑判断 */
	protected $page_tab = 'page_index';
	
	/* 默认模板 */
	protected $page_html = 'page/index.html';
}
?>