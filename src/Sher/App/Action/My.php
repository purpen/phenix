<?php
/**
 * 我的灵感库,其他关联操作等
 */
class Sher_App_Action_My extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
	    'url' => '',
	    'ref' => '',
		'stuff_id'=>'',
		'tags'=> '',
		'page'=>1,
		'content'=>'',
		'popup_mode' => 0,
		'my_dashboard' => true,
		'id' => null,
		'view_page' => null,
	);
	protected $page_tab = 'page_my';
	protected $page_html = 'page/my/my.html';
	
	public function _init() {
		$this->stash['user_id'] = $this->visitor->id;
        $this->stash['user'] = &$this->stash['visitor'];
		if (empty($this->stash['user'])) {
	       return $this->display_note_page('用户不存在或未登录，请先登录！');
	    }
		
		$this->set_target_css_state('user');
    }
	
	public function execute(){
		return false;
	}
	
	/**
	 * 设置用户头像
	 */
	public function avatar(){
		$this->set_target_css_state('user_avatar');
		return $this->to_html_page("page/my/avatar.html");
	}
	
	/**
	 * 设置个人信息
	 */
	public function profile(){
		$this->stash['profile'] = $this->visitor->profile;
		
		if(!isset($this->stash['user']['first_login']) || $this->stash['user']['first_login'] == 1){
			$this->stash['error_message'] = '请首先完善个人资料，再继续！';
		}
		
		$age = $this->visitor->age;
		$years = array();
		$mouths = array();
		$days = array();
		
		for($i=1970;$i<1997;$i++){
			$years[] = $i;
		}
		$this->stash['years'] = $years;
		
		for($m=1;$m<=12;$m++){
			if ($m < 10){
				$mouths[] = '0'.$m;
			}else{
				$mouths[] = $m;
			}
		}
		$this->stash['mouths'] = $mouths;
		
		for($d=1;$d<=31;$d++){
			if($d < 10){
				$days[] = '0'.$d;
			}else{
				$days[] = $d;
			}
		}
		$this->stash['days'] = $days;
		
		$this->set_target_css_state('user_profile');
		
		return $this->to_html_page("page/my/profile.html");
	}
	
	/**
	 * 上传照片
	 */
	public function photo(){
		$this->stash['profile'] = $this->visitor->profile;
		
		return $this->to_html_page("page/photo.html");
	}
	
	/**
	 * 邀请好友
	 */
	public function invite(){
		$invitation = new Sher_Core_Model_Invitation();
        $my_invites = $invitation->find(array('user_id' => $this->visitor->id),array('sort' => array('used_at' => -1)));
        $my_used_invites = array();
        $my_free_invites = array();
        if (!empty($my_invites)) {
            for ($i=0; $i < count($my_invites); $i++) {
                if ($my_invites[$i]['used']) {
                    $my_used_invites[] = $invitation->extend_load($my_invites[$i]['_id']);
                }
                else {
                    $my_free_invites[] = $my_invites[$i];
                }
            }
        }
        
        $this->stash['free_invites_cnt'] = count($my_free_invites);
        $this->stash['free_invites'] = $my_free_invites;
        $this->stash['used_invites_cnt'] = count($my_used_invites);
        $this->stash['used_invites'] = $my_used_invites;
		
		return $this->to_html_page("page/my/invite.html");
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
			return $this->ajax_note('请求失败,缺少必要参数',true);
		}
		if($follow_id == $user_id){
			return $this->ajax_note('请求失败,自己无法关注自己',true);
		}
		//验证是否超过最大关注数
		if($this->visitor->follow_count >= Sher_Core_Model_Follow::MAX_FOLLOW){
			return $this->ajax_note("请求失败,关注人数不能超过".Sher_Core_Model_Follow::MAX_FOLLOW."个",true);
		}
		
		$ship = new Sher_Core_Model_Follow();
		//添加关注
		if(!$ship->has_exist_ship($user_id,$follow_id)){
			$data['user_id'] = (int)$user_id;
			$data['follow_id'] = (int)$follow_id;
			
		    //验证关注者是否关注了自己
            if($ship->has_exist_ship($follow_id,$user_id)){
            	$data['type'] = Sher_Core_Model_Follow::BOTH_TYPE;
            	$is_both = true;
            }
			$ship->create($data);
            
			//更新关注数、粉丝数
			$this->visitor->inc_counter('fans_count', $follow_id);
			$this->visitor->inc_counter('follow_count', $user_id);
			unset($user);
			
			#更新新粉丝数
			$this->visitor->update_counter_byinc($follow_id,'fans_count');
			
			//更新粉丝相互关注状态
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
        //取消关注
        if($ship->has_exist_ship($user_id,$follow_id)){
	        $query['user_id'] = (int)$user_id;
	        $query['follow_id'] = (int)$follow_id;

	        $ship->remove($query);
	        //更新关注数、粉丝数
	        $this->visitor->dec_counter('fans_count',$follow_id);
	        $this->visitor->dec_counter('follow_count',$user_id);

	        //更新粉丝相互关注状态
	        $some_data['type'] = Lgk_Core_Model_Follow::ONE_TYPE;
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
	 * 发送红娘私信
	 */
	public function ajax_match() {
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
            $query = array('nickname'=>$to);
            $res = $user->first($query);
            if(!$res) {
                return $this->ajax_notification('发送的用户:'.$to.'不存在', true);
            }
			$to_id = $res['_id'];
			
			$msg = new Sher_Core_Model_Message();
            $_id = $msg->send_site_message($content, $this->visitor->id, $to_id);
        } catch (Doggy_Model_ValidateException $e) {
            return $this->ajax_notification('发送失败:'.$e->getMessage(),true);
        }
		
		$this->stash['mode'] = 'match';
		
		return $this->to_taconite_page('ajax/send_ok.html');
	}
	/**
	 * 更新用户行为记录标识
	 */
	public function ajax_update_visit() {
		$field = $this->stash['field'];
		$this->visitor->update_visit_field($this->visitor->id, $field);
		return $this->to_raw(200);
	}
	
	/**
	 * 更新某些记录后再跳转目标页
	 */
	public function update_before_redirect(){
		// 更新用户行为记录标识
		$field = $this->stash['field'];
		$this->visitor->update_visit_field($this->visitor->id, $field);
		
		$url = $this->stash['url'];
		return $this->to_redirect($url);
	}
	
	/**
	 * 编辑个人资料
	 */
    public function edit_profile() {
    	$this->stash['user_id'] = $this->visitor->id;
        $this->stash['user'] = &$this->stash['visitor'];
        
		
        return $this->to_html_page('page/my/profile.html');
    }
	/**
	 * 更新个人资料
	 */
    public function save_profile() {
		$user_info = array();
		$user_info['_id'] = $this->visitor->id;
		$user_info['nickname'] = $this->stash['nickname'];
		
		$profile = array();
        $profile['realname'] = $this->stash['realname'];
        $profile['job'] = $this->stash['job'];
		
		$user_info['profile'] = $profile;
        
		$user_info['sex'] = (int)$this->stash['sex'];
		$user_info['city'] = $this->stash['city'];
		$user_info['tags'] = $this->stash['tags'];
		$user_info['summary'] = $this->stash['summary'];
		
		$user_info['first_login'] = 0;
		
		
		$criteria = array('_id'=>$this->visitor->id);
		
        //更新基本信息
        $this->visitor->save($user_info);
        
        return $this->ajax_notification('你的个人资料更新成功！');
    }

	/**
     * 个人私信
     * 
     * @return string
     */
    public function dm_list() {
		$page = $this->stash['page'];
		
		# 更新通知数量
		$counter = $this->visitor->counter;
		if(isset($counter['message_count']) && $counter['message_count'] > 0){
			$this->visitor->update_counter($this->visitor->id,'message_count',0);
			$this->stash['visitor']['counter']['message_count'] = 0;
		}
		
		/* 消息模式 */
		$this->stash['message'] = true;
        $this->stash['pager_url'] = Doggy_Config::$vars['app.url.message'].'?page=#p#';
		
        return $this->display_tab_page('tab_message');
    }

	/**
     * 私信详情
     * 
     * @return string
     */
    public function message_detail() {
		/* 消息模式 */
		$this->stash['message'] = true;
		$mid = $this->stash['mid'];
		if(!$mid) {
			return $this->ajax_notification("私信参数不正确",true);
		}
		$mailbox = new Sher_Core_Model_Message();
		$result = $mailbox->extend_load((string)$mid);
		
    	if(empty($result)) {
			return $this->ajax_notification("没有相关私信",true);
		}
		$small_user = min($result['users']);
		$big_user = max($result['users']);
		
		# 更新通知数量
		$counter = $this->visitor->counter;		
		if($this->visitor->id == $small_user){
			if($result['s_readed']){
				$mailbox->mark_message_readed($mid,'s_readed');
				
				$message_count = $counter['message_count'] - $result['s_readed'];
				if($message_count > 0){
					$this->visitor->update_counter($this->visitor->id, 'message_count', $message_count);
				}else{
					$this->visitor->update_counter($this->visitor->id, 'message_count', 0);
				}
			}
			$to_user = $big_user;
		}else{
			if($result['b_readed']){
				$mailbox->mark_message_readed($mid,'b_readed');
				$message_count = $counter['message_count'] - $result['b_readed'];
				if($message_count > 0){
					$this->visitor->update_counter($this->visitor->id, 'message_count', $message_count);
				}else{
					$this->visitor->update_counter($this->visitor->id, 'message_count', 0);
				}
			}
			$to_user = $small_user;
		}
		$this->stash['visitor']['counter']['message_count'] = $message_count;
		
		$result['to_user']  = & DoggyX_Model_Mapper::load_model($to_user,'Sher_Core_Model_User');
		//翻转顺序
		$result['mailbox']  = array_reverse($result['mailbox']);
		
        $this->stash['msg'] = $result;
		
		return $this->display_tab_page('tab_message_detail');
    }
    
    /**
     * 删除照片
     */
	public function delete_photo() {
		$stuff_id = $this->stash['stuff_id'];
		
		$stuff = new Sher_Core_Model_Stuff();
		$row = $stuff->find_by_id($stuff_id);
		if(empty($row) || $row['user_id'] != $this->visitor->id){
			return $this->ajax_notification('数据有误，不能删除！');
		}
		$ok = $stuff->remove_all_links($stuff_id);
		if($ok){
			// 记录用户图片数量
			$this->visitor->dec_counter('photo_count');
		}
		
		$this->stash['id'] = $stuff_id;
		
		return $this->to_taconite_page('ajax/del_ok.html');
	}
}
?>