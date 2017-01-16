<?php
/**
 * 佣金结算记录管理
 * @author tianshuai
 */
class Sher_Admin_Action_BalanceRecord extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'status' => 0,
        'user_id' => '',
        'alliance_id' => '',
        'q' => '',
        't' => '',
	);
	
	public function _init() {
		$this->stash['show_type'] = "alliance";
		$this->set_target_css_state('page_balance_record');
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
        $status = $this->stash['status'];
        $t = (int)$this->stash['t'];
        $q = $this->stash['q'];

        switch($t){
            case 1:
                $this->stash['user_id'] = $q;
                break;
            case 2:
                $this->stash['alliance_id'] = $q;
                break;
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/balance_record?user_id=%d&alliance_id=%s&status=%d&t=%d&q=%s&page=#p#', $this->stash['user_id'], $this->stash['alliance_id'], $status, $t, $q);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/balance_record/list.html');
	}

    /**
     * 结算明细查询
     */
    public function item_list(){
		$page = (int)$this->stash['page'];
        $size = (int)$this->stash['size'];
        $balance_record_id = isset($this->stash['balance_record_id']) ? $this->stash['balance_record_id'] : null;
		$model = new Sher_Core_Model_BalanceItem();
        $query = array();
        if($balance_record_id){
            $query['balance_record_id'] = $balance_record_id;
        }
        $options = array('page'=>$page,'size'=>$size, 'sort'=>array('_id'=>1));
        $balance_items = $model->find($query, $options);
        $this->stash['balance_items'] = $balance_items;
		
        $total_count = $this->stash['total_count'] = $model->count($query);
        $this->stash['total_page'] = ceil($total_count/$size);
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/balance_record/item_list?balance_record_id=%s&page=#p#', $balance_record_id);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/balance_record/item_list.html');   
    
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

            $model = new Sher_Core_Model_BalanceRecord();
            
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
     * 结算每日统计列表
     */
    public function stat_list(){
		$page = (int)$this->stash['page'];
        $size = (int)$this->stash['size'];
        $day = isset($this->stash['day']) ? (int)$this->stash['day'] : 0;
        $alliance_id = isset($this->stash['alliance_id']) ? $this->stash['alliance_id'] : null;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;

        $query = array();

        if($day){
          $query['day'] = $day;
        }

        if($alliance_id){
          $query['alliance_id'] = $alliance_id;
        }

        if($user_id){
          $query['user_id'] = $user_id;
        }

        $options = array('page'=>$page, 'size'=>$size);

        $model = new Sher_Core_Model_BalanceStat();
        $obj = $model->find($query, $options);

        $total_count = $this->stash['total_count'] = $model->count($query);
        $this->stash['total_page'] = ceil($total_count/$size);
            
        $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/balance_record/stat_list?alliance_id=%s&user_id=%d&day=%d&page=#p#', $alliance_id, $user_id, $day);
            
        $this->stash['obj'] = $obj;
        $this->stash['pager_url'] = $pager_url;

		return $this->to_html_page('admin/balance_record/stat_list.html'); 
    
    }

    /**
     * 结算统计删除
     */
    public function stat_deleted(){
    
    
    }


}

