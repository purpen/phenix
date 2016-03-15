<?php
/**
 * 最近访客记录
 * @author tianshuai
 */
class Sher_Core_Model_RecentVisitor extends Sher_Core_Model_Base  {
	protected $collection = "recent_visitor";
	
	protected $schema = array(
    'user_id' => 0,
    'visitor_user_id' => 0,
    'visitor_count' => 1,
    // 最近一次访问时间
    'last_time' => 0,
    'kind' => 1,
		'state' => 1,
  	);

  protected $required_fields = array('user_id', 'visitor_user_id');

  protected $int_fields = array('state', 'user_id', 'visitor_count', 'kind', 'last_time');

	protected $joins = array(
		'visitor_user' =>  array('visitor_user_id' => 'Sher_Core_Model_User'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

  /**
   * 记录访客信息
   */
  public function record_visitor($user_id, $visitor_user_id, $options=array()){
    if($user_id == $visitor_user_id){
      return false;
    }
    // 先查找该用户是否访问过
    $has_one = $this->first(array('user_id'=>(int)$user_id, 'visitor_user_id'=>(int)$visitor_user_id));
    if($has_one){ // 更新
      $ok = $this->update_set((string)$has_one['_id'], array('last_time'=>time(), 'visitor_count'=>$has_one['visitor_count']+1));
    }else{  // 创建
      $ok = $this->create(array('user_id'=>(int)$user_id, 'visitor_user_id'=>(int)$visitor_user_id, 'last_time'=>time()));
    }
    return $ok;
  
  }
	
}

