<?php
/**
 * App模块基类
 * @author purpen
 */
class Sher_App_Action_Base extends Sher_Core_Action_Authorize {
	protected $dt_view_tags= array('sher_app');
	
    /* 页面标示,用于前台css高亮,模板逻辑判断 */
	protected $page_tab = 'page_index';
	
	/* 默认模板 */
	protected $page_html = 'page/index.html';
}
?>