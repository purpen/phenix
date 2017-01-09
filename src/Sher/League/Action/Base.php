<?php
/**
 * League模块基类
 * @author tianshuai
 */
class Sher_League_Action_Base extends Sher_Core_Action_Authorize {

	protected $dt_view_tags= array('sher_league');
	
    /* 页面标示,用于前台css高亮,模板逻辑判断 */
	//protected $page_tab = 'page_index';
	
	/* 默认模板 */
	protected $page_html = 'league/index.html';
}

