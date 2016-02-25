<?php
/**
 * 品牌管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_Brands extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'state' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_brands');
		$this->stash['show_type'] = "public";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
        
        $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/pusher/get_list?page=#p#';
		
		return $this->to_html_page('app_admin/brands/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
        // 活动头图，封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
        
		return $this->to_html_page('app_admin/brands/submit.html');
	}
    
    /**
	 * 更新
	 */
	public function edit(){
		
	}

	/**
	 * 保存信息
	 */
	public function save(){		

	}

	/**
	 * 删除
	 */
	public function delete(){
		
	}
}
