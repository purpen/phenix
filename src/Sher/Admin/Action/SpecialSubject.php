<?php
/**
 * 后台app专题管理
 * @author caowei@taihuoniao.com
 */
class Sher_Admin_Action_SpecialSubject extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 50,
	);
	
	public function _init() {
		$this->set_target_css_state('page_special_subject');
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

		$page = (int)$this->stash['page'];
		$this->stash['user_id'] = $this->visitor->id;
		
		//清空私信提醒数量
		if($this->visitor->counter['message_count']>0){
		  $this->visitor->update_counter($this->visitor->id, 'message_count');   
		}
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.admin'].'/private_letter/get_list?page=#p#';
		
		return $this->to_html_page('admin/special_subject/list.html');
	}
	
	/**
	 * 添加页面
	 */
	public function add(){
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SPECIAL_SUBJECT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SPECIAL_SUBJECT;
		$this->stash['mode'] = 'create';
		
		return $this->to_html_page('admin/special_subject/save.html');
	}
	
	/**
	 * 添加页面
	 */
	public function edit(){
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SPECIAL_SUBJECT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SPECIAL_SUBJECT;
		$this->stash['mode'] = 'edit';
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		// 验证id
		if(!$id){
			return $this->ajax_json('该专题不存在！', true);
		}
		
		$model = new Sher_Core_Model_SpecialSubject();
		$result = $model->extend_load((int)$id);
		//var_dump($result);
		$this->stash['special_subject'] = $result;
		
		return $this->to_html_page('admin/special_subject/save.html');
	}
	
	/**
	 * 保存方法
	 */
	public function save(){
		
		$id = (int)$this->stash['_id'];
		$special_subject_html = $this->stash['special_subject_html'];
		$special_subject_title = $this->stash['special_subject_title'];
		$special_subject_tag = $this->stash['special_subject_tag'];
		$product_ids = $this->stash['product_ids'];
		$cover_id = $this->stash['cover_id'];
		$category_id = $this->stash['category_id'];
		$kind = !empty($this->stash['kind']) ? $this->stash['kind'] : 2;
		
		// 验证内容
		if(!$special_subject_html){
			return $this->ajax_json('内容不能为空！', true);
		}
		
		// 验证标题
		if(!$special_subject_title){
			return $this->ajax_json('标题不能为空！', true);
		}
		
		// 验证标签
		if(!$special_subject_tag){
			return $this->ajax_json('标签不能为空！', true);
		}
		
		$tags_arr = array();
		$tags_arr = explode(',',$special_subject_tag);
		
		$product_ids_arr = array();
		$product_ids_arr = explode(',',$product_ids);
		
		$date = array(
			'title' => $special_subject_title,
			'tags' => $tags_arr,
			'product_ids' => $product_ids_arr,
			//'content' => htmlspecialchars_decode(htmlspecialchars($special_subject_html)),
			'content' => $special_subject_html,
			# 分类ID
			'category_id' => 1,
			'cover_id' => $cover_id,
			'kind' => (int)$kind,
			'category_id' => $category_id,
			'user_id' => (int)$this->visitor->id
		);
		//var_dump($date);die;
		
		try{
			$model = new Sher_Core_Model_SpecialSubject();
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
				$model->update_batch_assets($this->stash['asset'], (int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/special_subject';
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
			 $model = new Sher_Core_Model_SpecialSubject();
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
	* 推荐／取消
	*/
	public function ajax_stick(){
		
		$id = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		
		if(empty($id)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_SpecialSubject();
		$result = $model->mark_as_stick((int)$id, $evt);
		
		if(!$result['status']){
			return $this->ajax_notification($result['msg'], true);
		}
		
		return $this->to_taconite_page('admin/special_subject/stick_ok.html');
	}
}

