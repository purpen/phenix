<?php
/**
 * 合作资源(品牌、设计公司、生成商、材料供应商等)
 * @author purpen
 */
class Sher_App_Action_Cooperate extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'page'=>1,
        'd' => 0,
        'c' => 0,
	);
	
	protected $exclude_method_list = array('execute', 'index');
	
	public function _init() {
		$this->set_target_css_state('page_social');
        $this->set_target_css_state('page_cooperate');
    }
    
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 资源首页
	 */
	public function index(){
        $show_all = 'showno';
        $district = $this->stash['d'];
        $cid = $this->stash['c'];
        
        // 获取地域城市
        $areas = new Sher_Core_Model_Areas();
        $cities = $areas->find_cities();
        
        $model = new Sher_Core_Model_Cooperation();
        $resources = $model->find_resources();
        
        if($cid || $district){
            $show_all = 'showall';
        }
        
        $pager_url = sprintf(Doggy_Config::$vars['app.url.cooperate'].'?c=%d&d=%d&page=#p#', $cid, $district);
        
        $this->stash['cities'] = $cities;
        $this->stash['resources'] = $resources;
        
        $this->stash['cid'] = $cid;
        $this->stash['district'] = $district;
        $this->stash['show_all'] = $show_all;
        $this->stash['pager_url'] = $pager_url;
        
		return $this->to_html_page('page/cooperate/index.html');
	}
    
    /**
     * 详情查看
     */
    public function view(){
        $id = $this->stash['id'];
        $editable = false;
        
        $model = new Sher_Core_Model_Cooperation();
        $cooperate = $model->extend_load((int)$id);
        
        if($this->visitor->can_admin()){
            $editable = true;
        }
        
        $this->stash['cooperate'] = $cooperate;
        
        $this->stash['editable'] = $editable;
        
        return $this->to_html_page('page/cooperate/view.html');
    }
	
	/**
	 * 提交申请
	 */
	public function apply(){
		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_COOPERATE;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
        $model = new Sher_Core_Model_Cooperation();
        $resources = $model->find_resources();
        $this->stash['resources'] = $resources;
        
        $this->_editor_params();
		
		return $this->to_html_page('page/cooperate/apply.html');
	}
    
    /**
     * 编辑信息
     */
    public function edit(){
        $id = $this->stash['id'];
        
		$this->stash['mode'] = 'edit';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_COOPERATE;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
        
        $model = new Sher_Core_Model_Cooperation();
        $cooperate = $model->extend_load((int)$id);
        
        $this->stash['cooperate'] = $cooperate;
        
        $resources = $model->find_resources();
        $this->stash['resources'] = $resources;
        
        return $this->to_html_page('page/cooperate/apply.html');
    }
	
	/**
	 * 保存信息
	 */
	public function save(){
		// 验证数据
		if(empty($this->stash['name'])){
			return $this->ajax_json('名称不能为空！', true);
		}
        $mode = 'create';
        $data = $this->stash;
        
		$id = (int)$this->stash['_id'];
        
		// 检测编辑器图片数
		$file_count = isset($this->stash['file_count'])?(int)$this->stash['file_count']:0;
		
		// 检查是否有附件
		if(!isset($this->stash['asset'])){
			$data['asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Cooperation();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$cooperation = $model->get_data();
				
				$id = (int)$cooperation['_id'];
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], (int)$id);
			}
			
			// 保存成功后，更新编辑器图片
			Doggy_Log_Helper::debug("Upload file count[$file_count].");
			if($file_count && !empty($this->stash['file_id'])){
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
        
		$redirect_url = Sher_Core_Helper_Url::cooperate_home_url($id);
        
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF_EDITOR;
	}

	
}
?>
