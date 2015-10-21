<?php
/**
 * 活动专题提交
 * @author tianshuai
 */
class Sher_Wap_Action_PromoFunc extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
    'sort'=>0,
	);
	

	protected $exclude_method_list = array('execute', 'save_subject_sign');

	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}
	
 /**
   * 保存报名信息
   */
  public function save_subject_sign(){

    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?(int)$this->stash['event']:1;
    $user_id = isset($this->stash['user_id'])?(int)$this->stash['user_id']:0;

    if(empty($target_id)){
      return $this->ajax_json('参数不存在!', true);   
    }

    if(!preg_match("/1[3458]{1}\d{9}$/",trim($this->stash['phone']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($this->stash['phone'])));

    if($has_sign){
      return $this->ajax_json('您已经参与过了!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    $data = array();
    $data['user_id'] = $user_id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['info']['realname'] = $this->stash['realname'];
    $data['info']['phone'] = trim($this->stash['phone']);
    $data['info']['company'] = $this->stash['company'];
    $data['info']['job'] = $this->stash['job'];

    try{
      $ok = $model->apply_and_save($data);

      $user_data = array();

      if($this->visitor->id){
        if(empty($this->visitor->profile->realname)){
          $user_data['profile.realname'] = $this->stash['realname'];
        }
        if(empty($this->visitor->profile->phone)){
          $user_data['profile.phone'] = trim($this->stash['phone']);
        }
        if(empty($this->visitor->profile->company)){
          $user_data['profile.company'] = $this->stash['company'];
        }
        if(empty($this->visitor->profile->job)){
          $user_data['profile.job'] = $this->stash['job'];
        }

        //更新基本信息
        $user_ok = $this->visitor->update_set($this->visitor->id, $user_data);     
      
      }

      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==5){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/idea';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.wap'];
    	    $this->stash['note'] = '操作成功!';
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 2000;

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->ajax_json($this->stash['note'], false, $redirect_url);
      }else{
        return $this->ajax_json('保存失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_json('保存失败!'.$e->getMessage(), true);
    }
  
  }
	
}

