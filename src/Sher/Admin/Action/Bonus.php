<?php
/**
 * 红包管理
 * @author purpen
 */
class Sher_Admin_Action_Bonus extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'status' => 0,
		'used' => 0,
    'amount' => 0,
		'q' => '',
    'user_id' => null,
    'xname' => null,
	);
	
	public function _init() {
		$this->set_target_css_state('page_bonus');
		// 判断左栏类型
		$this->stash['show_type'] = "sales";
    }
	
	/**
	 * 入口
	 */
	public function execute() {

		return $this->get_list();
	}
	
	/**
	 * 红包列表
	 */
	public function get_list() {
		$page = (int)$this->stash['page'];
		Doggy_Log_Helper::warn("Get bonus list page[$page]!");
        
		if($this->stash['status'] == 1){
			$this->set_target_css_state('pending');
		}elseif($this->stash['status'] == 3){
			$this->set_target_css_state('locked');
		}elseif($this->stash['status'] == 4){
			$this->set_target_css_state('waited');
		}else{
			if ($this->stash['used'] == 0) {
				$this->set_target_css_state('all');
			}
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/bonus?status=%d&used=%d&amount=%d&user_id=%d&xname=%s&page=#p#', $this->stash['status'], $this->stash['used'], $this->stash['amount'], $this->stash['user_id'], $this->stash['xname']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/bonus/list.html');
	}
	
	/**
	 * 生成红包
	 */
	public function gen() {
		$bonus = new Sher_Core_Model_Bonus();		
		$bonus->create_batch_bonus(10);
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/bonus';
		return $this->to_redirect($pager_url);
	}
	
	/**
	 * 赠送
	 */
	public function give(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('红包不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Bonus();
			$bonus = $model->load($id);
			
			
			$this->stash['bonus'] = $bonus;
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_html_page('admin/bonus/give.html');
	}

	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_Bonus();
		if(!empty($id)){
			$mode = 'edit';
			$bonus = $model->find_by_id($id);
		    $bonus['_id'] = (string)$bonus['_id'];
			$this->stash['bonus'] = $bonus;

		}
		$this->stash['mode'] = $mode;
        $this->stash['xnames'] = $model->x_name();
		
		return $this->to_html_page('admin/bonus/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
        $id = $this->stash['_id'];
        $user_id = (int)$this->visitor->id;
        $xname = $this->stash['xname'];
        $amount_char = isset($this->stash['amount']) ? $this->stash['amount'] : null;
        $count = isset($this->stash['count']) ? (int)$this->stash['count'] : 1;
        $min_amount_char = isset($this->stash['min_amount']) ? $this->stash['min_amount'] : null;
        $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;

        if(empty($xname) || empty($amount_char) || empty($min_amount_char)){
            return $this->ajax_json('缺少请求参数!', true);
        }

        if($count>100){
            return $this->ajax_json('操作失败!', true);          
        }

		try{
			$model = new Sher_Core_Model_Bonus();
            $model->create_specify_bonus($count, $xname, $amount_char, $min_amount_char, $product_id);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save bonus failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}

		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/bonus';
		return $this->ajax_json('保存成功.', false, $redirect_url);
		

	}

	
	/**
	 * 赠送某人
	 */
	public function ajax_give(){
		$id = $this->stash['_id'];
		$user_id = $this->stash['user_id'];
        $expired_day = isset($this->stash['expired_day']) ? (int)$this->stash['expired_day'] : 0;
		if(empty($id) || empty($user_id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Bonus();
			$bonus = $model->load($id);
			
			if (empty($bonus)){
				return $this->ajax_json('红包不存在！', true);
			}
			// 是否使用过
			if ($bonus['used'] == Sher_Core_Model_Bonus::USED_OK){
				return $this->ajax_json('红包已被使用！', true);
			}
			//是否冻结中
			if ($bonus['status'] != Sher_Core_Model_Bonus::STATUS_OK){
				return $this->ajax_json('红包不能使用！', true);
			}
			// 是否过期
			if ($bonus['expired_at'] && $bonus['expired_at'] < time()){
				return $this->ajax_json('红包已被过期！', true);
			}

            if(!empty($expired_day)){
                $expired_time = time() + 60*60*24*$expired_day;
            }else{
                $expired_time = 0;
            }
			
			$ok = $model->give_user($bonus['code'], $user_id, $expired_time);
			
			$next_url = Doggy_Config::$vars['app.url.admin'].'/bonus?used='.$bonus['used'];
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试：'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('红包赠送成功！', false, $next_url);
	}
	
	/**
	 * 解冻
	 */
	public function unpending(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('红包不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Bonus();
			$model->unpending($id);
			
			$this->stash['id'] = $id;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 删除红包
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('红包不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Bonus();
			foreach($ids as $id){
				$bonus = $model->load($id);
				// 未使用红包允许删除
				if ($bonus['used'] == 1){
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
	 * 红包统计
	 */
	public function statistics() {
		$this->set_target_css_state('state');
		$model = new Sher_Core_Model_Bonus();
    $bonus = null;
    $this->stash['number_5'] = count($model->find(array('amount'=>5, 'status'=>4))) .'/'. count($model->find(array('amount'=>5)));
    $this->stash['number_10'] = count($model->find(array('amount'=>10, 'status'=>4))) .'/'. count($model->find(array('amount'=>10)));
    $this->stash['number_20'] = count($model->find(array('amount'=>20, 'status'=>4))) .'/'. count($model->find(array('amount'=>20)));
    $this->stash['number_50']  = count($model->find(array('amount'=>50, 'status'=>4))) .'/'. count($model->find(array('amount'=>50)));
    $this->stash['number_100']  = count($model->find(array('amount'=>100, 'status'=>4))) .'/'. count($model->find(array('amount'=>100)));
		
		return $this->to_html_page('admin/bonus/statistics.html');
	}

}
?>
