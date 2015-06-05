<?php
/**
 * 数量统计管理
 * @author tianshuai
 */
class Sher_Admin_Action_SumRecord extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'type' => 0,
    'target_id' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_sum_record');
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
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/sum_record?type=%s&page=#p#', $this->stash['type']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/sum_record/list.html');
	}


	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_SumRecord();

		if(!empty($id)){
			$mode = 'edit';
			$sum_record = $model->find_by_id($id);
			$this->stash['sum_record'] = $sum_record;
		}
    if($sum_record['type']==1 || $sum_record['type']==2){
      $this->stash['is_match2'] = true;
    }
    $this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/sum_record/submit.html');
	}

	/**
	 * 保存
	 */
	public function save() {
		
		$model = new Sher_Core_Model_SumRecord();
		try{
      $data = array();
			if(empty($this->stash['_id'])){
				$mode = 'create';
				//$ok = $model->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
        $data['_id'] = (string)$this->stash['_id'];
        if(isset($this->stash['match2_count'])){
          $data['match2_count'] = (int)$this->stash['match2_count'];      
        }
        if(isset($this->stash['match2_love_count'])){
          $data['match2_love_count'] = (int)$this->stash['match2_love_count'];
        }
        $data['count'] = (int)$this->stash['count'];

				$ok = $model->apply_and_update($data);
			}
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/sum_record';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('记录不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_SumRecord();
			
			foreach($ids as $id){
				$sum_record = $model->load((string)$id);
				
        if (!empty($sum_record)){
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
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/sum_record/search?s=%d&q=%s&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q']);
    return $this->to_html_page('admin/sum_record/list.html');
  
  }

}

