<?php
/**
 * 话题管理
 * @author tianshuai
 */
class Sher_Admin_Action_Topic extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'sort' => 0,
    'start_date' => '',
    'end_date' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_topic');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表--全部
	 */
	public function get_list() {
    $this->set_target_css_state('all_list');
		$page = (int)$this->stash['page'];

		$this->stash['category_id'] = 0;
		$this->stash['is_top'] = true;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/topic?sort=%d&page=#p#', $this->stash['sort']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/topic/list.html');
	}

	/**
	 * 创建/更新
	 */
	public function submit(){
		
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		$mode = 'create';
		$model = new Sher_Core_Model_Topic();

		if(!empty($id)){
			$mode = 'edit';
			$topic = $model->find_by_id($id);
			$topic = $model->extended_model_row($topic);
			$this->stash['topic'] = $topic;
		}
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/topic/submit.html');
	}

	/**
	 * 保存
	 */
	public function save() {
		
		$model = new Sher_Core_Model_Topic();
		try{
      $data = array();
			if(empty($this->stash['_id'])){
				$mode = 'create';
				//$ok = $model->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
        $data['_id'] = (int)$this->stash['_id'];
        $data['try_id'] = isset($this->stash['try_id']) ? (int)$this->stash['try_id'] : 0;
				$ok = $model->apply_and_update($data);
			}
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/topic';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('话题不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Topic();
			
			foreach($ids as $id){
				$topic = $model->load((int)$id);
				
        if (!empty($topic)){
		      $model->remove((int)$id);
			    $model->mock_after_remove($id, $topic);
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
    $this->stash['start_time'] = $this->stash['end_time'] = 0;
    if($this->stash['start_date']){
      $this->stash['start_time'] = strtotime($this->stash['start_date']);
    }

    if($this->stash['end_date']){
      $this->stash['end_time'] = strtotime($this->stash['end_date']);  
    }
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/topic/search?s=%d&q=%s&sort=%d&start_time=%d&end_time=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q'], $this->stash['sort'], $this->stash['start_time'], $this->stash['end_time']);
    return $this->to_html_page('admin/topic/list.html');
  
  }

  /**
   * 推荐／取消
   */
  public function ajax_stick(){
 		$ids = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Topic();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$result = $model->mark_as_stick((int)$id, $evt);
		}
		
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
  
  }

  /**
   * 点赞名单
   */
  public function get_love_list(){
    
    return $this->to_html_page('admin/topic/love_list.html');
  }

}

