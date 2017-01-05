<?php
/**
 * 佣金结算单管理
 * @author tianshuai
 */
class Sher_Admin_Action_Balance extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'kind' => 0,
        'type' => 0,
        'status' => 0,
        'stage' => 0,
        'user_id' => '',
        'alliance_id' => '',
        'product_id' => '',
        't' => '',
        'q' => '',
	);
	
	public function _init() {
		$this->stash['show_type'] = "alliance";
		$this->set_target_css_state('page_balance');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
        $this->set_target_css_state('all');
		$page = $this->stash['page'];
        $user_id = $this->stash['user_id'];
        $kind = $this->stash['kind'];
        $type = $this->stash['type'];
        $status = $this->stash['status'];
        $stage = $this->stash['stage'];

        $t = (int)$this->stash['t'];
        $q = $this->stash['q'];

        switch($t){
            case 1:
                $this->stash['user_id'] = $q;
                break;
            case 2:
                $this->stash['alliance_id'] = $q;
                break;
            case 3:
                $this->stash['product_id'] = $q;
                break;
            case 4:
                $this->stash['order_rid'] = $q;
                break;
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/balance?user_id=%d&kind=%d&type=%d&status=%d&stage=%d&t=%d&q=%s&page=#p#', $user_id, $kind, $type, $status, $stage, $t, $q);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/balance/list.html');
	}


	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
        $user_id = $this->visitor->id;
		if(empty($id)){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));

        if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
            return $this->ajax_notification('没有执行权限!', true);     
        }
		
		try{

            $model = new Sher_Core_Model_Balance();
            
            foreach($ids as $id){
                $balance = $model->load($id);
                
                if (!empty($balance)){

                    $model->remove($id);
                    // 删除关联对象
                    $model->mock_after_remove($id, $balance);
                }
            }   // endfor
            
            $this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

    /**
     * 审核
     */
    public function ajax_set_status(){
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 1;

        $model = new Sher_Core_Model_Balance();
        $ok = $model->mark_as_status($id, $status);
        if(!$ok){
            return $this->ajax_json('更新失败！', true);
        }

        return $this->ajax_json('操作成功！', false, 0, array('id'=>$id));
    
    }

}

