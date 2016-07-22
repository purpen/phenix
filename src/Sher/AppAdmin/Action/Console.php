<?php
/**
 * App后台管理功能
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Console extends Sher_AppAdmin_Action_Base {
	
	public $stash = array(
		'category_id' => 0,
		'page' => 1,
		'sort' => 'latest',
		'rank' => 'day',
    'uuid' => null,
    'channel_id' => null,
    'kind' => 1,
    'device' => 1,
    'month' => null,
    'week' => null,
    'day' => null,
	);
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->dashboard();
	}
	
  /**
   * App管理首页
   * @return string
   */
  public function dashboard() {
    	$this->set_target_css_state('page_dashboard');

		$this->stash['admin'] = true;
		
		// 判断左栏类型
		$this->stash['show_type'] = "console";
		
    return $this->to_html_page('app_admin/dashboard.html');
  }

  /**
   * 商城激活量记录
   */
  public function user_active_record(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_app_user_record');

		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/console/user_active_record?uuid=%s&channel_id=%d&kind=%d&device=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['uuid'], $this->stash['channel_id'], $this->stash['kind'], $this->stash['device']);

    return $this->to_html_page('app_admin/user_active_record.html');

  }

  /**
   * Fiu激活量记录
   */
  public function fiu_user_active_record(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_fiu_user_record');

		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/console/fiu_user_active_record?uuid=%s&channel_id=%d&kind=%d&device=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['uuid'], $this->stash['channel_id'], $this->stash['kind'], $this->stash['device']);

    return $this->to_html_page('app_admin/fiu_user_active_record.html');

  }

  /**
   * 商城激活量删除
   */
  public function app_user_record_deleted(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_AppUserRecord();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');

  }

  /**
   * Fiu激活量删除
   */
  public function fiu_user_record_deleted(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_FiuUserRecord();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');

  }

  /**
   * 用户统计-商城
   */
  public function user_stat(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_app_store_user_stat');
    $this->set_target_css_state('all_list');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/console/user_stat?month=%s&week=%s&day=%s&page=#p#', $this->stash['month'], $this->stash['week'], $this->stash['day']);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('app_admin/user_stat.html');

  }

  /**
   * 用户统计-Fiu
   */
  public function fiu_user_stat(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_app_fiu_user_stat');
    $this->set_target_css_state('all_list');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/console/fiu_user_stat?month=%s&week=%s&day=%s&page=#p#', $this->stash['month'], $this->stash['week'], $this->stash['day']);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('app_admin/fiu_user_stat.html');

  }

  /**
   * 用户统计删除操作-商城
   */
  public function user_stat_delete(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
    $user_id = $this->visitor->id;
    if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
 			return $this->ajax_note('没有执行权限!', true);     
    }
		$model = new Sher_Core_Model_AppStoreUserStat();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html'); 
  }

  /**
   * 用户统计删除操作-Fiu
   */
  public function fiu_user_stat_delete(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
    $user_id = $this->visitor->id;
    if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
 			return $this->ajax_note('没有执行权限!', true);     
    }
		$model = new Sher_Core_Model_AppFiuUserStat();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html'); 
  }

  /**
   * ajax查询某个渠道统计
   */
  public function ajax_channel_search(){
    $channel_id = isset($this->stash['channel_id']) ? (int)$this->stash['channel_id'] : 0;
    $start_date = isset($this->stash['start_date']) ? $this->stash['start_date'] : null;
    $end_date = isset($this->stash['end_date']) ? $this->stash['end_date'] : null;

    $star_tmp = !empty($start_date) ? strtotime($start_date) : 0;
    $end_tmp = !empty($end_date) ? strtotime($end_date) : 0; 

    // 激活量查询
    // android|ios
    $current_android_count = $this->fetch_count(1, 1, $channel_id, $star_tmp, $end_tmp);

    // 注册量查询
    // android|ios
    $current_android_grow_count = $this->fetch_grow_count(2, $channel_id, $star_tmp, $end_tmp);

    // 有效订单查询
    $current_order = $this->fetch_order_count($channel_id, $star_tmp, $end_tmp);

    $data = array(
      'android_count' => $current_android_count,
      'android_grow_count' => $current_android_grow_count,
      'order_count' => $current_order['count'],
      'order_money' => $current_order['total_money'],
    );

    return $this->ajax_json('success', false, 0, $data);

  }

  /**
   *  注册量
   */
  protected function fetch_grow_count($from_to, $channel_id=0, $star_tmp=0, $end_tmp=0){
    $query['from_to'] = $from_to;
    if($channel_id){
      $query['channel_id'] = $channel_id;
    }
    if(!empty($star_tmp) && !empty($end_tmp)){
      $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
    }

    $pusher_model = new Sher_Core_Model_Pusher();
    $count = $pusher_model->count($query);
    return $count;
  }

  /**
   *  激活量
   */
  protected function fetch_count($kind, $device, $channel_id=0, $star_tmp=0, $end_tmp=0){
    $query['kind'] = $kind;
    $query['device'] = $device;
    if($channel_id){
      $query['channel_id'] = $channel_id;
    }
    if(!empty($star_tmp) && !empty($end_tmp)){
      $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
    }

    $app_user_record_model = new Sher_Core_Model_AppUserRecord();
    $count = $app_user_record_model->count($query);
    return $count;
  }

  /**
   *  有效订单量
   */
  protected function fetch_order_count($channel_id=0, $star_tmp=0, $end_tmp=0){
		// 设置不超时
		set_time_limit(0);

    $query['status'] = array('$in'=>array(10,15,16,20));
    if($channel_id){
      $query['channel_id'] = $channel_id;
    }
    if(!empty($star_tmp) && !empty($end_tmp)){
      $query['payed_date'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
    }

		$options = array();
		$page = 1;
		$size = 500;
		
    $order_model = new Sher_Core_Model_Orders();
		
		$is_end = false;
		$counter = 0;
    $total_money = 0;
    $options['size'] = $size;
		
		while(!$is_end){
			$options['page'] = $page;
			
			$result = $order_model->find($query, $options);
			$max = count($result);
			for($i=0; $i<$max; $i++){
        $order = $result[$i];
				$counter ++;
        $total_money += $order['pay_money'];
			}
			
			if($max < $size){
				$is_end = true;
				break;
			}
			
			$page++;
		} // end while
    return array('count'=>$counter, 'total_money'=>$total_money);

  }

	
}

