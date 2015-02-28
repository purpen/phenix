<?php
/**
 * 大赛-十万火计
 * @author purpen
 */
class Sher_Wap_Action_Contest extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'category_id' => 0,
	);
	
	protected $exclude_method_list = array('execute','dream', 'dream2', 'topic', 'allist', 'allist2', 'get_list', 'show');
	
	/**
	 * 社区入口
	 */
	public function execute(){
		return $this->topic();
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match.html');
	}

	/**
	 * 十万火计 第２季
	 */
	public function dream2(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		$this->stash['start_time'] = mktime(0,0,0,2,10,2015);
		$this->stash['end_time'] = mktime(23,59,59,6,20,2015);
		
		return $this->to_html_page('wap/contest/match2.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'dream', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match_list.html');
	}

	/**
	 * 全部创意列表 第２季
	 */
	public function allist2(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'dream', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/contest/list2.html');
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		return $this->to_html_page('wap/contest/submit.html');
	}
	
}
?>
