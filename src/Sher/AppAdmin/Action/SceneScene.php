<?php
/**
 * 情景管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneScene extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
        'deleted' => 0,
	);
	
	public function _init() {
		
		$this->set_target_css_state('page_app_scene_scene');
		$this->stash['show_type'] = "sight";

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
        
        if($this->stash['deleted']==1){
  		    $this->set_target_css_state('deleted');      
        }else{
            $this->set_target_css_state('all');
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_scene/get_list?deleted=%d&page=#p#', $this->stash['deleted']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_scene/list.html');
	}
    
    /**
	 * 更新
	 */
	public function submit(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;

		$fid = Doggy_Config::$vars['app.scene.category_id'];
        $this->stash['fid'] = $fid;

        if(empty($id)){
            $mode = 'create';       
        }else{
		    $mode = 'edit';
            $model = new Sher_Core_Model_SceneScene();
            $result = $model->first((int)$id);
            $result = $model->extended_model_row($result);       
		    $this->stash['scene'] = $result;
        }

		$this->stash['mode'] = $mode;
		$this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];
		
		// 封面/头像/Banner图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;

		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['avatar_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_BANNER;
		$this->stash['avatar_asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_AVATAR;
		
		return $this->to_html_page('app_admin/scene_scene/submit.html');
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		$user_id = $this->visitor->id;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$cover_id = isset($this->stash['cover_id']) ? $this->stash['cover_id'] : null;
		$banner_id = isset($this->stash['banner_id']) ? $this->stash['banner_id'] : null;
		$avatar_id = isset($this->stash['avatar_id']) ? $this->stash['avatar_id'] : null;
        $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
        $bright_spot = isset($this->stash['bright_spot']) ? (array)$this->stash['bright_spot'] : array();
		$alliance_code = isset($this->stash['alliance_code']) ? $this->stash['alliance_code'] : '';
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['sub_title'] = $this->stash['sub_title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
        $data['city'] = isset($this->stash['city']) ? $this->stash['city'] : '';
        $data['address'] = $this->stash['address'];
        $data['category_id'] = $category_id;
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		$data['cover_id'] = $cover_id;
		$data['banner_id'] = $banner_id;
		$data['avatar_id'] = $avatar_id;

        $data['score_average'] = 5;
        $data['bright_spot'] = $bright_spot;
        $data['alliance_code'] = $alliance_code;

        if(isset($this->stash['user_id']) && !empty($this->stash['user_id'])){
            $data['user_id'] = (int)$this->stash['user_id'];
        }

        $extra = array(
            'shop_hours' => $this->stash['extra_shop_hours'],
            'tel' => $this->stash['extra_tel'],
        );
        $data['extra'] = $extra;
		
		if(empty($data['title']) || empty($data['des'])){
			return $this->ajax_json('请求参数不能为空', true);
		}

		if(empty($data['category_id'])){
			return $this->ajax_json('分类不能为空', true);
		}
		
		if(empty($data['address']) || empty($data['address'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		if(empty($data['location']['coordinates'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
			// 新建记录
			if(empty($id)){
                if(empty($data['user_id'])){
				    $data['user_id'] = $user_id;
                }

                $exist = $model->first(array('user_id'=>$data['user_id']));
                if($exist){
 				    return $this->ajax_json('每个用户只能绑定一个地盘!', true);               
                }
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
                if($ok){
                    // 更新关联用户表
                    $user_model = new Sher_Core_Model_User();
                    $user_model->update_set($data['user_id'], array('identify.storage_id'=>$id));
                }
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}

            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig((int)$id, 4);
			
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$model->update_batch_assets($this->stash['asset'], $id);
            }
			if(isset($this->stash['avatar_asset']) && !empty($this->stash['avatar_asset'])){
				$model->update_batch_assets($this->stash['avatar_asset'], $id);
            }
			if(isset($this->stash['banner_asset']) && !empty($this->stash['banner_asset'])){
				$model->update_batch_assets($this->stash['banner_asset'], $id);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("地盘保存失败：".$e->getMessage());
			return $this->ajax_json('地盘保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('提交成功', false, null);
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
            if($evt==0){
                $model->mark_cancel_stick($id);
            }else{
                $model->mark_as_stick($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/stick_ok.html');
	}

	/**
	 * 精选
	 */
	public function ajax_fine() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
            if($evt==0){
                $model->mark_cancel_fine($id);
            }else{
                $model->mark_as_fine($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/fine_ok.html');
	}
	
	/**
	 * 审核
	 */
	public function ajax_check() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
			$model->update_set($id, array('is_check'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/check_ok.html');
	}

	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}

        if(!Sher_Core_Helper_Util::is_high_admin($this->visitor->id)){
            return $this->ajax_notification('没有执行权限!', true);     
        }

		$model = new Sher_Core_Model_SceneScene();
		$result = $model->load($id);
		
		if($result && $model->mark_remove($id)){
            $model->mock_after_remove($id, $result);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}

    /**
     * 产品列表
     */
    public function product_list(){
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
        $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
        $scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;

        $query = array();
        $options = array();
		if($scene_id){
			$query['scene_id'] = $scene_id;
		}
		
		if($product_id){
			$query['product_id'] = $product_id;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		
		// 开启查询
        $service = Sher_Core_Service_ZoneProductLink::instance();
        $result = $service->get_zone_product_list($query, $options);

        $product_model = new Sher_Core_Model_Product();
        $scene_model = new Sher_Core_Model_SceneScene();

        // 重新数据
        for($i=0;$i<count($result['rows']);$i++){
            $item = $result['rows'][$i];
            $result['rows'][$i]['_id'] = (string)$item['_id'];

            $result['rows'][$i]['product'] = $product_model->extend_load($item['product_id']);
            $result['rows'][$i]['scene'] = $scene_model->extend_load($item['scene_id']);
        }

		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_scene/product_list?scene_id=%d&product_id=%d&page=#p#', $scene_id, $product_id);
		$this->stash['pager_url'] = $pager_url;
        $this->stash['result'] = $result;

		return $this->to_html_page('app_admin/scene_scene/product_list.html');
    }

	/**
	 * 删除地盘下相关产品
	 */
	public function scene_product_delete() {
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}

		$model = new Sher_Core_Model_ZoneProductLink();
		$result = $model->load($id);
		
		if($result && $model->remove($id)){
            $model->mock_after_remove($id, $result);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}

    /**
     * ajax添加地盘产品
     */
    public function add_scene_product(){
        $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
        $scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		if(empty($product_id) || empty($scene_id)){
			return $this->ajax_json('请求参数为空!', true);
		}

        $row = array(
            'scene_id' => $scene_id,
            'product_id' => $product_id,
        );
		$model = new Sher_Core_Model_ZoneProductLink();
        $item = $model->first(array('scene_id'=>$scene_id, 'product_id'=>$product_id));
        if(!empty($item)){
 		    return $this->ajax_json('不能重复添加!', true);           
        }

        $ok = $model->apply_and_save($row);
        if(!$ok){
  		    return $this->ajax_json('添加失败!', true);            
        }
        $item = $model->get_data();
        $id = (string)$item['_id'];
  		return $this->ajax_json('success!', false, null, array('id'=>$id));
    }

}
