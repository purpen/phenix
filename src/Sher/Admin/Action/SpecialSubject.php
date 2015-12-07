<?php
/**
 * 后台app专题管理
 * @author caowei@taihuoniao.com
 */
class Sher_Admin_Action_SpecialSubject extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
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
	public function add_page(){
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SPECIAL_SUBJECT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SPECIAL_SUBJECT;
		$this->stash['mode'] = 'create';
		
		return $this->to_html_page('admin/special_subject/save.html');
	}
	
	/**
	 * 保存方法
	 */
	public function save(){
		echo 'ok';
		$id = $this->stash['_id'];
		$special_subject_html = $this->stash['special_subject_html'];
		$special_subject_title = $this->stash['special_subject_title'];
		$special_subject_tag = $this->stash['special_subject_tag'];
		
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
		
		$date = array(
			'title' => $special_subject_title,
			'tags' => $tags_arr,
			'content' => $special_subject_html,
			'category_id' => '0',
			'user_id' => (int)$this->visitor->id
		);
		//var_dump($date);die;
		
		try{
			$model = new Sher_Core_Model_SpecialSubject();
			if(empty($id)){
				// add
				$ok = $model->apply_and_save($date);
			} else {
				// edit
				$date['_id'] = $id;
				$ok = $model->apply_and_update($date);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('保存成功！', false);
	}
}

