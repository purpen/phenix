<?php
/**
 * 情境专题管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_SceneSubject extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'kind' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_scene_subject');
		// 判断左栏类型
		$this->stash['show_type'] = "sight";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

		$this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_subject/get_list?kind=%d&page=#p#', $this->stash['kind']);
		return $this->to_html_page('app_admin/scene_subject/list.html');
	}
	
	/**
	 * 添加页面
	 */
	public function submit(){

		$this->stash['mode'] = 'edit';
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;

		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_subject';
		if(empty($id)){
            $this->stash['mode'] = 'create';
        }else{
            $this->stash['mode'] = 'edit';
            $model = new Sher_Core_Model_SceneSubject();
            $scene_subject = $model->extend_load($id);
            if(empty($scene_subject)){
			    return $this->show_message_page('专题不存在！', $redirect_url);
            }
            $this->stash['scene_subject'] = $scene_subject;
        }

		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SUBJECT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT;
		
		return $this->to_html_page('app_admin/scene_subject/submit.html');
	}
	
	/**
	 * 保存方法
	 */
	public function save(){
		
		$id = (int)$this->stash['_id'];
		$scene_subject_html = $this->stash['scene_subject_html'];
		$scene_subject_title = $this->stash['scene_subject_title'];
		$scene_subject_tag = $this->stash['scene_subject_tag'];
		$product_ids = $this->stash['product_ids'];
		$cover_id = $this->stash['cover_id'];
		$category_id = $this->stash['category_id'];
		$kind = !empty($this->stash['kind']) ? $this->stash['kind'] : 2;
		
		// 验证内容
		if(!$scene_subject_html){
			return $this->ajax_json('内容不能为空！', true);
		}
		
		// 验证标题
		if(!$scene_subject_title){
			return $this->ajax_json('标题不能为空！', true);
		}
		
		// 验证标签
		if(!$scene_subject_tag){
			return $this->ajax_json('标签不能为空！', true);
		}
		
		$tags_arr = array();
		$tags_arr = explode(',',$scene_subject_tag);
		
		$product_ids_arr = array();
		$product_ids_arr = explode(',',$product_ids);
		
		$date = array(
			'title' => $scene_subject_title,
			'tags' => $tags_arr,
			'product_ids' => $product_ids_arr,
			//'content' => htmlscenechars_decode(htmlscenechars($scene_subject_html)),
			'content' => $scene_subject_html,
			# 分类ID
			'category_id' => 1,
			'cover_id' => $cover_id,
			'kind' => (int)$kind,
			'category_id' => $category_id,
      'summary' => $this->stash['summary'],
		);
		
		try{
			$model = new Sher_Core_Model_SceneSubject();
			if(empty($id)){
				// add
        $date['user_id'] = $this->visitor->id;
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
				$model->update_batch_assets($this->stash['asset'], (int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/scene_subject';
		return $this->ajax_json('保存成功', false, $redirect_url);
	}
	
	/**
	* 删除专题
	*/
	public function deleted(){
	   
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		
		if(empty($id)){
		   return $this->ajax_notification('专题信息不存在！', true);
		}
	   
		try{
			 $model = new Sher_Core_Model_SceneSubject();
			 $ok = $model->remove((int)$id);
			 
			 if(!$ok){
				 return $this->ajax_json('保存失败,请重新提交', true);
			 }
			 
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		$this->stash['ids'] = $ids;
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	* 发布／取消
	*/
	public function ajax_publish(){
		
		$id = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		
		if(empty($id)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_SceneSubject();
		$result = $model->mark_as_publish((int)$id, $evt);
		
		if(!$result['status']){
			return $this->ajax_notification($result['msg'], true);
		}
		
		return $this->to_taconite_page('app_admin/scene_subject/publish_ok.html');
	}
	
	/**
	* 推荐／取消
	*/
	public function ajax_stick(){
		
		$id = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		
		if(empty($id)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_SceneSubject();
		$result = $model->mark_as_stick((int)$id, $evt);
		
		if(!$result['status']){
			return $this->ajax_notification($result['msg'], true);
		}
		
		return $this->to_taconite_page('app_admin/scene_subject/stick_ok.html');
	}
}

