<?php
/**
 * 推荐管理
 * @author tianshuai
 */
class Sher_Admin_Action_Dig extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_dig');
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
    $this->set_target_css_state('page_all');

    $model = new Sher_Core_Model_DigList();
    $query = array();
    $options['page'] = $this->stash['page'];
    $options['size'] = 10;
    //$options['sort'] = array();
    $data = $model->find($query, $options);
    foreach($data as $key=>$val){
      $data[$key] = $model->extended_model_row($val);
    }

    $this->stash['digs'] = $data;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/dig?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/dig/list.html');
	}

	/**
	 * 创建/更新
	 */
	public function edit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_DigList();
		if(!empty($id)){
			$mode = 'edit';
			$dig = $model->find_by_id($id);
      $dig = $model->extended_model_row($dig);
      $dig['_id'] = (string)$dig['_id'];
			$this->stash['dig'] = $dig;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/dig/edit.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('块不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_DigList();
			
			foreach($ids as $id){
				$dig = $model->load($id);
				
				if (!empty($dig)){
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
   * 改变大赛中奖状态
   */
  public function ajax_change_match_praise(){
    $user = isset($this->stash['user'])?(int)$this->stash['user']:0;
    $account = isset($this->stash['account'])?$this->stash['account']:0;
    $praise = isset($this->stash['praise'])?(int)$this->stash['praise']:0;
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
    $is_del = isset($this->stash['is_del'])?(int)$this->stash['is_del']:0;

    $digged = new Sher_Core_Model_DigList();
    $key_id = Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT;

    if($evt==0){
      $evt_new = 1;
    }else{
      $evt_new = 0;
    }   

    $item_new = array('user'=>$user, 'account'=>$account, 'praise'=>$praise, 'evt'=>$evt_new);
    $item = array('user'=>$user, 'account'=>$account, 'praise'=>$praise, 'evt'=>$evt);
    if($is_del){
      $digged->remove_item_custom($key_id, $item);   
    }else{
      $digged->remove_item_custom($key_id, $item);
      $digged->add_item_custom($key_id, $item_new);
    }
    
  }

}

