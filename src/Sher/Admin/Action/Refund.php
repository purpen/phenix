<?php
/**
 * 售后管理
 * @author tianshuai
 */
class Sher_Admin_Action_Refund extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'user_id' => '',
        'stage' => '',
        'type' => '',
	);
    
	public function _init() {
		$this->set_target_css_state('page_refund');
		// 判断左栏类型
		$this->stash['show_type'] = "sales";
    }
	
	public function execute(){
		return $this->get_list();
	}

	/**
     * 列表
     * @return string
     */
    public function get_list() {
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/refund?user_id=%d&type=%d&stage=%d&page=#p#';
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['user_id'], $this->stash['type'], $this->stash['stage']);
		
        return $this->to_html_page('admin/refund/list.html');
    }
	
	/**
	 * 删除
	 */
	public function deleted() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->ajax_notification('退款单不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Refund();
			
			foreach($ids as $id){
				$result = $model->load($id);
				if (!empty($result)){
					$model->remove($id);
					// 删除关联对象
					$model->mock_after_remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

    /**
     * 修改价格
     */
    public function modify_price(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$new_price = isset($this->stash['new_price']) ? (float)$this->stash['new_price'] : 0;
		if(empty($id) || empty($new_price)){
			return $this->ajax_json('缺少请求参数！', true);
		}
        $model = new Sher_Core_Model_Refund();
        $refund = $model->load($id);
        if(empty($refund)){
  		    return $this->ajax_json('退款单不存在！', true);      
        }
        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
   		    return $this->ajax_json('该状态不允许修改！', true);       
        }

        $update = array();
        $update['refund_price'] = $new_price;
        $update['change_user_id'] = $this->visitor->id;
        if(!isset($refund['old_price']) || empty($refund['old_price'])){
            $old_price = $refund['refund_price'];
            $update['old_price'] = $old_price;
        }

        $ok = $model->update_set($id, $update);
        if(!$ok){
    	    return $this->ajax_json('修改失败！', true);           
        }
        return $this->ajax_json('success', false, null, array('id'=>$id));
    
    }

    /**
     * 拒绝退款
     */
    public function ajax_reject_refund(){
 		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$reason = isset($this->stash['reason']) ? $this->stash['reason'] : null;
		if(empty($id) || empty($reason)){
			return $this->ajax_json('缺少请求参数！', true);
		}
        $model = new Sher_Core_Model_Refund();
        $refund = $model->load($id);
        if(empty($refund)){
  		    return $this->ajax_json('退款单不存在！', true);      
        }
        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
   		    return $this->ajax_json('该状态不允许修改！', true);
        }

        $ok = $model->reject_refund($id, array('reason'=>$reason));
        if(!$ok){
            return $this->ajax_json('操作失败!', true);
        }
        return $this->ajax_json('success!', false);
    }

}
