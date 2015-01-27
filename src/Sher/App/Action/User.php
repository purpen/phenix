<?php
/**
 * 个人主页
 */
class Sher_App_Action_User extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'user_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/user/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
        $user_id = $this->stash['id'];
        $this->stash['user'] = array();
        if (!empty($user_id)) {
            $this->stash['user'] = &DoggyX_Model_Mapper::load_model((int)$user_id,'Sher_Core_Model_User');
        }
    }

	/**
	 * 用户
	 */
	public function execute(){
		if (empty($this->stash['user'])) {
	       return $this->display_note_page('用户不存在');
	    }
		return $this->vcenter();
	}
	
	/**
	 * 用户个人主页
	 */
	public function vcenter(){
		$this->set_target_css_state('home');
		$follow_id = $this->stash['id'];
		
		// 本人首次登录，需先完成资料
		if($this->visitor->id == (int)$this->stash['id'] && $this->stash['visitor']['first_login'] == 1){
			$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile?first_login=1';
			return $this->to_redirect($user_profile_url);
		}
		
		$this->stash['profile'] = $this->stash['user']['profile'];
		
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $follow_id);
		
		$this->stash['is_ship'] = $is_ship;
		
		return $this->display_tab_page('tab_all');
	}
	
	/**
	 * 支持的产品(包括：投票、预定)
	 */
	public function support(){
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_support_list_url($this->stash['user_id'], '#p#');
		
		return $this->display_tab_page('tab_support');
	}
	
	/**
	 * 喜欢的产品
	 */
	public function like(){
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_like_list_url($this->stash['user_id'], '#p#');
		
		return $this->display_tab_page('tab_like');
	}
	
	/**
	 * 发起的产品
	 */
	public function submitted(){
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_submitted_list_url($this->stash['user_id'], '#p#');
		
		return $this->display_tab_page('tab_submitted');
	}
	
	
	
	
	/**
	 * 查看个人资料
	 */
	public function profile(){
		
	}
	
	
	
	/**
	 * 我的粉丝
	 */
	public function fans(){
		$page = $this->stash['page'];
		$this->set_target_css_state('home');
		$this->stash['profile'] = $this->stash['user']['profile'];
		
        $this->stash['pager_url'] = Sher_Core_Helper_Url::user_fans_list_url($this->stash['user_id'],'#p#');
		
		# 更新粉丝
		$counter = $this->visitor->counter;
		if(isset($counter['fans_count']) && $counter['fans_count'] > 0){
			$this->visitor->update_counter($this->visitor->id,'fans_count',0);
			$this->stash['visitor']['counter']['fans_count'] = 0;
		}
		
        return $this->display_tab_page('tab_fans');
	}
	
	/**
	 * 我的关注者
	 */
	public function follow(){
		$page = $this->stash['page'];
		
		$this->set_target_css_state('home');
		$this->stash['profile'] = $this->stash['user']['profile'];
		
        $this->stash['pager_url'] = Sher_Core_Helper_Url::user_follow_list_url($this->stash['user_id'],'#p#');
		
        return $this->display_tab_page('tab_follow');
	}
	
	/**
	 * 关注某人
	 * 
	 * @return string
	 */
	public function ajax_follow(){
		$user_id = $this->visitor->id;
		$follow_id = $this->stash['id'];
		
		if(empty($follow_id) || empty($user_id)){
			return $this->ajax_note('请求失败,缺少必要参数', true);
		}
		if($follow_id == $user_id){
			return $this->ajax_note('请求失败,自己无法关注自己', true);
		}
		// 验证是否超过最大关注数
		if($this->visitor->follow_count >= Sher_Core_Model_Follow::MAX_FOLLOW){
			return $this->ajax_note("请求失败,关注人数不能超过".Sher_Core_Model_Follow::MAX_FOLLOW."个", true);
		}
		
		$ship = new Sher_Core_Model_Follow();
		// 添加关注
		$is_both = false;
		if(!$ship->has_exist_ship($user_id,$follow_id)){
			$data['user_id'] = (int)$user_id;
			$data['follow_id'] = (int)$follow_id;
			
		    // 验证关注者是否关注了自己
            if($ship->has_exist_ship($follow_id,$user_id)){
            	$data['type'] = Sher_Core_Model_Follow::BOTH_TYPE;
            	$is_both = true;
            }
			$ship->create($data);
            
			// 更新关注数、粉丝数
			$this->visitor->inc_counter('fans_count', $follow_id);
			$this->visitor->inc_counter('follow_count', $user_id);
			unset($user);
			
			// 更新粉丝相互关注状态
			if($is_both){
				$some_data['type'] = Sher_Core_Model_Follow::BOTH_TYPE;
                $update['user_id'] = (int)$follow_id;
                $update['follow_id'] = (int)$user_id;
				
                $ship->update_set($update,$some_data);
			}
		}
		
		$this->stash['domode'] = 'create';
		
		return $this->to_taconite_page('ajax/follow_ok.html');
	}
	
	/**
	 * 取消关注某人
	 * 
	 * @return string
	 */
	public function ajax_cancel_follow(){
		$user_id = $this->visitor->id;
        $follow_id = $this->stash['id'];
        
        if(empty($follow_id) || empty($user_id)){
            return $this->ajax_note('请求失败,缺少必要参数',true);
        }
		
        $ship = new Sher_Core_Model_Follow();
        // 取消关注
        if($ship->has_exist_ship($user_id,$follow_id)){
	        $query['user_id'] = (int)$user_id;
	        $query['follow_id'] = (int)$follow_id;

	        $ship->remove($query);
			
	        // 更新关注数、粉丝数
	        $this->visitor->dec_counter('fans_count', $follow_id);
	        $this->visitor->dec_counter('follow_count', $user_id);

	        // 更新粉丝相互关注状态
	        $some_data['type'] = Sher_Core_Model_Follow::ONE_TYPE;
	        $update['user_id'] = (int)$follow_id;
	        $update['follow_id'] = (int)$user_id;
			
	        $ship->update_set($update,$some_data);
        }
		
        $this->stash['domode'] = 'cancel';
		
        return $this->to_taconite_page('ajax/follow_ok.html');
	}
	
	/**
	 * 发送私信
	 * 
	 * @return string
	 */
	public function ajax_message(){
		$to = $this->stash["to"];
        $content = $this->stash["content"];
		if(empty($to)){
            return $this->ajax_notification("你没有选择发送的用户",true);
        }
        if(empty($content)){
            return $this->ajax_notification("你没有输入私信内容",true);
        }
		try {
            $user = new Sher_Core_Model_User();
            $res = $user->find_by_id((int)$to);
            if(!$res) {
                return $this->ajax_notification('发送的用户ID:'.$to.'不存在', true);
            }
			
			$msg = new Sher_Core_Model_Message();
            $_id = $msg->send_site_message($content, $this->visitor->id, $to);
        } catch (Doggy_Model_ValidateException $e) {
            return $this->ajax_notification('发送私信失败:'.$e->getMessage(),true);
        }
		
		$this->stash['mode'] = 'message';
		
		return $this->to_taconite_page('ajax/send_ok.html');
	}

	/**
	 * 定时获取用户消息提醒
	 * 
	 * @return string
	 */
	public function ajax_fetch_counter(){
    $this->stash['total_count'] = (
		  ($this->stash['message_count'] = $this->visitor->counter['message_count']) +
		  ($this->stash['alert_count'] = $this->visitor->counter['alert_count']) +
		  ($this->stash['notice_count'] = $this->visitor->counter['notice_count']) +
		  ($this->stash['fans_count'] = $this->visitor->counter['fans_count']) +
		  ($this->stash['comment_count'] = $this->visitor->counter['comment_count']) +
		  ($this->stash['people_count'] = $this->visitor->counter['people_count'])
    );
    return $this->to_taconite_page('ajax/user_notice.html');
	}

  /**
   * 验证用户基本资料是否齐全
   */
  public function ajax_check_userinfo(){
  
    if($this->visitor->id && !empty($this->visitor->profile)){
      if(empty($this->visitor->profile['realname'])){
        return $this->to_raw_json(false);
      }
      if(empty($this->visitor->profile['phone'])){
        return $this->to_raw_json(false);
      }
      if(empty($this->visitor->profile['job'])){
        return $this->to_raw_json(false);
      }
      if(empty($this->visitor->profile['address'])){
        return $this->to_raw_json(false);
      }
    }else{
      return $this->to_raw_json(false);
    }
    return $this->to_raw_json(true);
  }
	
	
}
?>
