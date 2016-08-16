<?php
/**
 * 品牌管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_Brands extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
        'kind' => '',
        'mark' => '',
        'from_to' => '',
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
        $kind = (int)$this->stash['kind'];
        switch($kind){
            case 1:
                $this->set_target_css_state('fiu');
                break;
            case 2:
                $this->set_target_css_state('store');
                break;
            default:
                $this->set_target_css_state('all');
                break;
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/brands/get_list?kind=%d&mark=%s&from_to=%d&page=#p#', $kind, $this->stash['mark'], $this->stash['from_to']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/brands/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';

        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'];
		
        // 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_BANNER;
        
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

        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'];
		
		// 封面图/Banner图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_BANNER;
		
		$model = new Sher_Core_Model_SceneBrands();
		$result = $model->find_by_id($id);
		$result = $model->extended_model_row($result);
		
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/brands/submit.html');
		
	}

	/**
	 * 保存信息
	 */
    public function save(){

        $redirect_url = isset($this->stash['return_url']) ? htmlspecialchars_decode($this->stash['return_url']) : null;
		
		$id = $this->stash['id'];
		$title = $this->stash['title'];
		$des = $this->stash['des'];
		$cover_id = isset($this->stash['cover_id']) ? $this->stash['cover_id'] : null;
		$banner_id = isset($this->stash['banner_id']) ? $this->stash['banner_id'] : null;
		$kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 0;
		$mark = isset($this->stash['mark']) ? strtolower($this->stash['mark']) : '';
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
		$self_run = isset($this->stash['self_run']) ? (int)$this->stash['self_run'] : 0;
		$tags = isset($this->stash['tags']) ? $this->stash['tags'] : null;
		
		// 验证内容
		if(!$title){
			return $this->ajax_json('品牌名称不能为空！', true);
		}
		
		// 验证图片
		if(!$cover_id){
			return $this->ajax_json('图片不能为空！', true);
		}

		// 验证类型
		if(!$kind){
			return $this->ajax_json('请选择类型！', true);
		}
		
		$date = array(
			'title' => $title,
			'des' => $des,
			'cover_id' => $cover_id,
            'banner_id' => $banner_id,
            'kind' => $kind,
            'mark' => $mark,
            'from_to' => $from_to,
            'self_run' => $self_run,
            'tags' => $tags,
            
		);

		try{
			$model = new Sher_Core_Model_SceneBrands();
			if(empty($id)){
				// add
                $date['user_id'] = $this->visitor->id;
				$ok = $model->apply_and_save($date);
				$data_id = $model->get_data();
				$id = (string)$data_id['_id'];
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
				$model->update_batch_assets($this->stash['asset'], $id);
			}

			// 上传成功后，更新所属的附件(Banner)
			if(isset($this->stash['banner_asset']) && !empty($this->stash['banner_asset'])){
				$model->update_batch_assets($this->stash['banner_asset'], $id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}

        if(!$redirect_url){
		    $redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/brands';
        }
		
		return $this->ajax_json('保存成功!', false, $redirect_url);
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
	
	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneBrands();
			$model->update_set($id, array('stick'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/brands/stick_ok.html');
	}

    /**
     * 产品列表
     */
    public function product_list() {
        $brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : null;
		if(empty($brand_id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/brands/product_list?page=#p#';
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/brands/product_list.html');
    }
}
