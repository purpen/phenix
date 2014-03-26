<?php
/**
 * 社区帖子
 * @author purpen
 */
class Sher_App_Action_Topic extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'cover_id' => 0,
		'categroy_id' => 0,
		'sort' => 'time',
		'page' => 1,
		'ref' => null,
	);
	
	protected $page_tab = 'page_topic';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_topic');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 社区列表
	 */
	public function get_list(){
		return $this->to_html_page('page/topic/list.html');
	}
	
	/**
	 * 显示主题详情帖
	 */
	public function show(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = & $model->extend_load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->show_message_page('访问的主题不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['topic'] = &$topic;
		
		return $this->to_html_page('page/topic/show.html');
	}
	
	
	/**
	 * 提交创意
	 */
	public function submit(){
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 编辑修改主题
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('您编辑的主题不存在！', true);
		}
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load((int)$this->stash['id']);
		
		$this->stash['topic'] = $topic;
		
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 保存主题信息
	 */
	public function save(){
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}
		$id = (int)$this->stash['_id'];
		$mode = 'create';
		
		$data = array();
		
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		$data['category_id'] = $this->stash['category_id'];
		$data['video_url'] = $this->stash['video_url'];
		$data['asset'] = $this->stash['asset'];
		$data['asset_count'] = count($data['asset']);
		
		try{
			$model = new Sher_Core_Model_Topic();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				$topic = $model->get_data();
				
				$id = $topic['_id'];
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Sher_Core_Helper_Url::topic_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 批量更新附件所属
	 */
	protected function update_batch_assets($ids=array(), $parent_id){
		if (!empty($assets)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
	}
	
	
}
?>