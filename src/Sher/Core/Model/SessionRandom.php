<?php
/**
 * session random 通过session 生成随机数
 * @author tianshuai
 */
class Sher_Core_Model_SessionRandom extends Sher_Core_Model_Base  {
	protected $collection = "session_random";
	
	protected $schema = array(
    'session_id' => null,
    'random' => null,
    'kind' => 1,
    'state' => 1,
    # 跳转地址
    'redirect_url' => null,
  	);

  protected $required_fields = array('session_id', 'random');

  protected $int_fields = array('state', 'kind');


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
   * 生成数据 
   */
  public function gen_random($session_id, $random, $kind=1, $redirect_url=null){
    if(empty($session_id) || empty($random)){
      return false;
    }
    $is_exist = $this->first(array('session_id'=>$session_id, 'kind'=>(int)$kind));
    if($is_exist){
      $ok = $this->update_set((string)$is_exist['_id'], array('random'=>$random));      
    }else{
      $ok = $this->create(array('session_id'=>$session_id, 'random'=>$random, 'kind'=>(int)$kind), 'redirect_url'=>$redirect_url);    
    }
    return $ok;
  }

  /**
   * 查询
   */
  public function is_exist($session_id, $random, $kind=1){
    if(empty($session_id) || empty($random)){
      return false;
    }
    $ok = $this->first(array('session_id'=>$session_id, 'random'=>$random, 'kind'=>(int)$kind));
    if($ok){
      return $ok;
    }else{
      return false;
    }
  }
	
}

