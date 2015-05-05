<?php
/**
 * 专题管理
 * @author tianshuai
 */
class Sher_Admin_Action_Special extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_special');
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
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/list.html');
	}

  /**
   * 名单
   */
  public function get_attend_list(){
		$page = (int)$this->stash['page'];
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:1;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special/get_attend_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/attend_list.html');
  }

  /**
   * 通过/拒绝
   */
  public function ajax_state(){
    $id = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
    if(empty($id)){
			return $this->ajax_notification('缺少参数！', true);
    }

		$model = new Sher_Core_Model_SubjectRecord();
    $user_model = new Sher_Core_Model_User();
		$result = $model->mark_as_state((string)$id, $state);
    if($result['status']){
      $this->stash['success'] = true;
      $target_id = $result['target_id'];
      $number = isset($result['number'])?$result['number']:0;
      //通过后生成号码
      if($state==1 && empty($number)){
        $is_exist_random = false;
        while(!$is_exist_random){
          $number = rand(100000, 999999);
          $has_one = $model->first(array('target_id'=>$target_id, 'number'=>$number));
          $is_exist_random = !empty($has_one)?false:true;
        }
        $this->stash['number'] = $number;
        $ok = $model->update_set((string)$id, array('number' => $number));     
      }
      
      //京东众筹-短信私信提醒
      if($target_id==2){
        $user = $user_model->find_by_id((int)$result['user_id']);
        if(!empty($user)){
          //短信提醒
          if(!empty($user['profile']['phone'])){
            if($state==1 && !empty($number)){
              $msg = "恭喜您，您报名的“2015京东众筹BIGGER大会”已经通过审核，您的票号：".$number."，太火鸟诚邀您出席。时间：2015/5/7-14:00；地点：海上五号棚·上海徐汇区漕溪北路595号上海电影广场，更多详情：http://dwz.cn/IQPkW";
            }elseif($state==2){
              $msg = "亲爱的会员您好：我们很抱歉的通知您，由于报名人数过多，会场空间有限，您报名的“2015京东众筹BIGGER大会”未能通过审核。更多精彩活动请关注：http://dwz.cn/ISlKP";
            }
            // 开始发送
            $message = Sher_Core_Helper_Util::send_defined_mms($user['profile']['phone'], $msg);
          }

          //私信提醒
          if(!empty($user['profile']['phone'])){
            if($state==1 && !empty($number)){
              $msg = "恭喜您，您报名的“2015京东众筹BIGGER大会”已经通过审核，您的票号：".$number."，太火鸟诚邀您出席。时间：2015/5/7-14:00；地点：海上五号棚·上海徐汇区漕溪北路595号上海电影广场.";
            }elseif($state==2){
              $msg = "亲爱的会员您好：我们很抱歉的通知您，由于报名人数过多，会场空间有限，您报名的“2015京东众筹BIGGER大会”未能通过审核。更多精彩活动请关注太火鸟官网";
            }
			      $message = new Sher_Core_Model_Message();
            $message->send_site_message($msg, $this->visitor->id, $user['_id']);
          }
          
        }
      }

    }else{
 		  $this->stash['success'] = false;   
    }
    $this->stash['message'] = $result['msg'];
    $this->stash['state'] = $state;

		return $this->to_taconite_page('admin/special/ajax_state.html');

  }

}
