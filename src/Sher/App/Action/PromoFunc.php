<?php
/**
 * 活动专题方法页面
 * @author tianshuai
 */
class Sher_App_Action_PromoFunc extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}


  /**
   * 申请支持
   */
  public function ajax_attend(){
    $user_id = $this->visitor->id;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 1;
    $cid = isset($this->stash['cid']) ? (int)$this->stash['cid'] : 0;
    if(empty($target_id)){
      return $this->ajax_json('缺少请求参数', true);
    } 

    $mode_attend = new Sher_Core_Model_Attend();
    $is_attend = $mode_attend->check_signup($user_id, $target_id, $event);
    if($is_attend){
      return $this->ajax_json('您已经支持过了', true);   
    }

    $data = array(
      'user_id' => $user_id,
      'target_id' => $target_id,
      'event'  => $event,
      'cid' => $cid,
    );

    $ok = $mode_attend->create($data);
    if($ok){
      $dig_key = null;
      $dig_model = new Sher_Core_Model_DigList();
      switch($target_id){
      case 1:
        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;
        break;
      case 5:
        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;
        break;
      }


      if($dig_key){
        if(!empty($cid)){
          if($cid==1) $dig_model->inc($dig_key, 'items.count_01', 1);
          if($cid==2) $dig_model->inc($dig_key, 'items.count_02', 1);
        }

        $dig_model->inc($dig_key, 'items.total_count', 1);
      }

      return $this->ajax_json('支持成功，赶快评论赢取奖品！', false, '', $data);
    }else{
      return $this->ajax_json('哟，出问题了!', true);     
    }

  }

  /**
   * ajax验证用户是否申请过
   */
  public function ajax_check_attend(){
    $user_id = $this->visitor->id;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 1;
    if(empty($target_id)){
      return $this->ajax_json('缺少请求参数', true);
    }
    $result = $this->check_user_attend($user_id, $target_id, $event);
    return $this->ajax_json('ok', false, '', $result);
  }


  /**
   * 验证用户是否已报名或支持过
   */
  protected function check_user_attend($user_id, $target_id, $event=1){
    $mode_attend = new Sher_Core_Model_Attend();
    return $mode_attend->check_signup($user_id, $target_id, $event);
  }


}

