<?php
/**
 * Attend商城首页推荐管理
 * @author tianshuai
 */
class Sher_Admin_Action_Attend extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'event' => 6,
	);
	
	public function _init() {
		$this->set_target_css_state('page_attend');
		// 判断左栏类型
		$this->stash['show_type'] = "assist";
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
		
        $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/attend/get_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/attend/list.html');
	}

    /**
     * 添加/编辑
     */
    public function submit(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$fid = Doggy_Config::$vars['app.default_app_store_stick.category_id'];
        $this->stash['fid'] = $fid;

		$this->stash['mode'] = 'create';
		
		$attend = array();
		if (!empty($id)){
			$model = new Sher_Core_Model_Attend();
			$attend = $model->load($id);
			$attend = $model->extended_model_row($attend);
			
			$this->stash['mode'] = 'edit';
		}
		$this->stash['attend'] = $attend;

		return $this->to_html_page('admin/attend/submit.html');
    }

	/**
	 * 保存
	 */
	public function save(){		
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;

        $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
        $cid = isset($this->stash['cid']) ? (int)$this->stash['cid'] : 0;
        $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
        $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;

        $title = isset($this->stash['title']) ? $this->stash['title'] : null;
        $content = isset($this->stash['content']) ? $this->stash['content'] : null;
		
        $mode = 'create';

		// 验证内容
		if(!$target_id){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		$data = array(
			'category_id' => $category_id,
			'cid' => $cid,
            'event' => $event,
            'target_id' => $target_id,
		);

        if(!empty($title) || !empty($content)){
            $data['info']['title'] = $title;
            $data['info']['content'] = $content;
        }

		try{
			$model = new Sher_Core_Model_Attend();
			if(empty($id)){
				// add
                $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				$data_id = $model->get_data();
				$id = $data_id['_id'];
			} else {
				// edit
                $mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/attend';
		return $this->ajax_json('保存成功', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('ID不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Attend();
			foreach($ids as $id){
				$record = $model->load($id);
				// 
				if ($record){
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
	 * 推荐/取消推荐
	 */
	public function ajax_set_stick() {

		if(empty($this->stash['id'])){
			return $this->ajax_note('缺少请求参数！', true);
		}
    $evt = $this->stash['evt'] = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		
		try{
			$model = new Sher_Core_Model_Attend();
      if($evt){
        $model->mark_as_stick($this->stash['id']);     
      }else{
        $model->mark_cancel_stick($this->stash['id']);
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('admin/attend/stick_ok.html');
	}

}
