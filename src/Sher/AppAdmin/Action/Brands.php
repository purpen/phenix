<?php
/**
 * 品牌管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_Brands extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
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
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/brands/get_list?page=#p#';
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/brands/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		
        // 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
        
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/brands/submit.html');
	}
    
    /**
	 * 更新
	 */
	public function edit(){
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
		if(!$id){
			return $this->ajax_json('内容不能为空！', true);
		}
		$mode = 'edit';
		
		// 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
		
		$model = new Sher_Core_Model_SceneBrands();
		$result = $model->find_by_id($id);
		$result = $model->extended_model_row($result);
		//var_dump($result);
		
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/brands/submit.html');
		
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		
		$id = $this->stash['id'];
		$title = $this->stash['title'];
		$des = $this->stash['des'];
		$cover_id = $this->stash['cover_id'];
		
		// 验证内容
		if(!$title){
			return $this->ajax_json('品牌名称不能为空！', true);
		}
		
		// 验证标题
		if(!$cover_id){
			return $this->ajax_json('封面不能为空！', true);
		}
		
		$date = array(
			'title' => $title,
			'des' => $des,
			'cover_id' => $cover_id,
		);
		//var_dump($date);die;
		try{
			$model = new Sher_Core_Model_SceneBrands();
			if(empty($id)){
				// add
				$ok = $model->apply_and_save($date);
				$data_id = $model->get_data();
				$id = $data_id['_id'];
			} else {
				// edit
				$date['_id'] = $id;
				$ok = $model->apply_and_update($date);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$model->update_batch_assets($this->stash['asset'], (string)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/brands';
		return $this->ajax_json('保存成功', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function delete(){
		
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_SceneBrands();
			
			foreach($ids as $id){
				$result = $model->load($id);
				
				if (!empty($result)){
					$model->remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
}
