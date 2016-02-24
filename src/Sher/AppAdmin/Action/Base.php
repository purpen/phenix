<?php
/**
 * App后台管理控制台
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Base extends Sher_Core_Action_Authorize {
	
    /* 页面标示,用于前台css高亮,模板逻辑判断 */
	protected $page_tab = 'app_admin_index';
	
	/* 默认模板 */
	protected $page_html = 'app_admin/index.html';
	
	protected $exclude_method_list = array();
	
	protected $admin_method_list = '*';
	
}
