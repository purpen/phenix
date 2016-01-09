<?php
/**
 * 评论管理
 * @author tianshuai
 */
class Sher_Admin_Action_Comment extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    's' => 0,
    't' => 0,
    'c' => '',
    'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_comment');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/comment/search?s=%d&q=%s&c=%s&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q'], $this->stash['c']);
		
		return $this->to_html_page('admin/comment/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Comment();

		if(!empty($id)){
			$mode = 'edit';
			$comment = $model->find_by_id($id);
      $comment = $model->extended_model_row($comment);
			$this->stash['comment'] = $comment;
		}
		return $this->to_html_page('admin/comment/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;

		$data = array();
		$data['content'] = $this->stash['content'];
		$data['love_count'] = (int)$this->stash['love_count'];
		$data['floor'] = (int)$this->stash['floor'];

		try{
			$model = new Sher_Core_Model_Comment();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = $model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save admin comment failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/comment';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('评论不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Comment();
			
			foreach($ids as $id){
				$comment = $model->load($id);
				
        if (!empty($comment)){
          //逻辑删除
					$model->remove((string)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 搜索
   */
  public function search(){
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/comment/search?s=%d&q=%s&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q']);
    return $this->to_html_page('admin/comment/list.html');
  
  }

  /**
   * 赞名单
   */
  public function get_attend_list(){
		$page = (int)$this->stash['page'];
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/comment/get_attend_list?target_id=%s&page=#p#', $this->stash['target_id']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/comment/attend_list.html');
  }

}

