<?php
/**
 * 个人主页
 * @author purpen
 */
class Sher_App_Action_User extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'user_id' => '',
        'uid' => '',
		'page' => 1,
        't' => 1,
	);
	
	protected $page_tab  = 'page_user';
	protected $page_html = 'page/user/index.html';
	
	protected $exclude_method_list = array('execute', 'vcenter', 'ajax_fetch_activity', 'topics', 'fans', 'follow', 'submitted', 'support', 'like', 'ajax_fetch_profile', 'ajax_fetch_user_sign', 'ajax_sign_in');
	
	public function _init() {
        $user_id = $this->stash['id'];
        $this->stash['user'] = array();
        if (!empty($user_id)) {
            $user = new Sher_Core_Model_User();
            $row = $user->load((int)$user_id);
            if(!empty($row)){
                $this->stash['user'] = $user->extended_model_row($row);
            }
            // 用户实时积分
            $point_model = new Sher_Core_Model_UserPointBalance();
            $current_point = $point_model->load((int)$user_id);
            $this->stash['current_point'] = $current_point;
        }
        $this->stash['last_char'] = substr((string)$user_id, -1);
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
		
		// 本人首次登录，需先完成资料---不需要了
		if($this->visitor->id == (int)$this->stash['id'] && $this->stash['visitor']['first_login'] == 1){
      $this->stash['is_first_login'] = 1;
			//$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile?first_login=1';
			//return $this->to_redirect($user_profile_url);
		}
		
		$this->stash['profile'] = $this->stash['user']['profile'];
		
		// 验证关注关系
        $this->validate_ship();
		
		return $this->display_tab_page('tab_all');
	}
    
    /**
     * 加载更多动态
     */
    public function ajax_fetch_activity(){
        $page = (int)$this->stash['page'];
        $user_id = (int)$this->stash['uid'];
        
        $service = Sher_Core_Service_Timeline::instance();
        $query = array(
            'user_id' => $user_id,
        );
        $options = array(
            'page' => $page,
            'size' => 10,
            'sort_field' => 'latest',
        );
        $timelist = $service->get_latest_list($query, $options);
        
        $next_page = 'no';
        if(isset($timelist['next_page'])){
            if($timelist['next_page'] > $page){
                $next_page = $timelist['next_page'];
            }
        }
        
        $this->stash['timelist']  = $timelist;
        $this->stash['nex_page'] = $next_page;
        
        return $this->to_taconite_page('ajax/activity_list.html');
    }
    
    /**
     * 验证关系
     */
    protected function validate_ship(){
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $this->stash['id']);
		$this->stash['is_ship'] = $is_ship;
    }
    
    /**
     * 参与的话题
     */
    public function topics(){
        $t = $this->stash['t'];
        if($t == 1){
            $this->set_target_css_state('type_post');
        }elseif($t == 2){
            $this->set_target_css_state('type_love');
        }
        
        $pager_url = Sher_Core_Helper_Url::user_topic_list_url($this->stash['id'], '#p#', $t);
        $this->stash['pager_url'] = $pager_url;
        
        $this->set_target_css_state('tab_topic');
        
		// 验证关注关系
        $this->validate_ship();
        
        return $this->to_html_page("page/user/topics.html");
    }
    
	/**
	 * 我的粉丝
	 */
	public function fans(){
		$page = $this->stash['page'];
        
		$this->stash['profile'] = $this->stash['user']['profile'];
		
        $this->stash['pager_url'] = Sher_Core_Helper_Url::user_fans_list_url($this->stash['id'], '#p#');
		
        $this->set_target_css_state('tab_fans');
        
		// 验证关注关系
        $this->validate_ship();
        
        return $this->to_html_page("page/user/fans.html");
	}
    
	/**
	 * 我的关注者
	 */
	public function follow(){
		$page = $this->stash['page'];
		
		$this->stash['profile'] = $this->stash['user']['profile'];
		
        $this->stash['pager_url'] = Sher_Core_Helper_Url::user_follow_list_url($this->stash['id'], '#p#');
		
        $this->set_target_css_state('tab_follow');
        
		// 验证关注关系
        $this->validate_ship();
        
        return $this->to_html_page("page/user/follow.html");
	}
    
	/**
	 * 发起的产品
	 */
	public function submitted(){
        $this->set_target_css_state('tab_submit');
        
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_submitted_list_url($this->stash['id'], '#p#');
		
        $this->set_target_css_state('tab_product');
        
		// 验证关注关系
        $this->validate_ship();
        
        return $this->to_html_page("page/user/products.html");
	}
	
	/**
	 * 支持的产品(包括：投票、预定)
	 */
	public function support(){
        $this->set_target_css_state('tab_support');
        
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_support_list_url($this->stash['id'], '#p#');
		
        $this->set_target_css_state('tab_product');
        
		// 验证关注关系
        $this->validate_ship();
        
		return $this->to_html_page("page/user/products.html");
	}
	
	/**
	 * 喜欢的产品
	 */
	public function like(){
        $this->set_target_css_state('tab_like');
        
		$this->stash['pager_url'] = Sher_Core_Helper_Url::user_like_list_url($this->stash['id'], '#p#');
        
		$this->set_target_css_state('tab_product');
        
		// 验证关注关系
        $this->validate_ship();
        
		return $this->to_html_page("page/user/products.html");
	}
	
	
	/**
	 * 查看个人资料
	 */
	public function profile(){}
	
	/**
	 * 关注某人
	 * 
	 * @return string
	 */
	public function ajax_follow(){
		$user_id = $this->visitor->id;
		$follow_id = $this->stash['id'];
        $this->stash['follow_type'] = isset($this->stash['follow_type'])?(int)$this->stash['follow_type']:1;
		
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
        $this->stash['follow_type'] = isset($this->stash['follow_type'])?(int)$this->stash['follow_type']:1;
        
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
        $from_to = isset($this->stash['from_to'])?$this->stash['from_to']:1;
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
            //产品合作私信给用户短信提醒
            if($from_to==2){
              $phone = (int)$this->stash['user_phone'];
              // 开始发送
              //$msg = "管理员查看了您提交的孵化项目并给您发了私信,请登录太火鸟官网查看! 【太火鸟】";
              //Sher_Core_Helper_Util::send_defined_mms($phone, $msg);
            }


        } catch (Doggy_Model_ValidateException $e) {
            return $this->ajax_notification('发送私信失败:'.$e->getMessage(),true);
        }
		
		$this->stash['mode'] = 'message';
		
		return $this->ajax_json('success!', false);
	}


	/**
	 * 定时获取用户消息提醒
	 * 
	 * @return string
	 */
	public function ajax_fetch_counter(){
        $model = new Sher_Core_Model_User();
        $user = $model->load((int)$this->visitor->id);
        $data = array();
        $data = false;
        if(empty($user)){
            return $this->to_taconite_page('ajax/user_notice.html');
        }
        $data['total_count'] = (
		  ($data['message_count'] = $user['counter']['message_count']) +
		  ($data['alert_count'] = $user['counter']['alert_count']) +
		  ($data['notice_count'] = $user['counter']['notice_count']) +
		  ($data['fans_count'] = $user['counter']['fans_count']) +
		  ($data['comment_count'] = $user['counter']['comment_count']) +
		  ($data['people_count'] = $user['counter']['people_count'])
        );

        if((int)$data['total_count'] > 0){
            $data['page_notice_success'] = true;   
        }
        
        return $this->ajax_json('', false, '', $data);
	}
    
    /**
     * 获取用户信息
     */
    public function ajax_fetch_profile(){
        $user = $this->stash['user'];
        
        // 验证是否关注
        $this->validate_ship();
        
        return $this->ajax_json('', false, '', $this->stash);
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

    /**
     * 举报
     */
    public function ajax_report(){
        $target_id = isset($this->stash['target_id'])?$this->stash['target_id']:0;
        $target_type = isset($this->stash['target_type'])?(int)$this->stash['target_type']:1;
        $target_user_id = isset($this->stash['target_user_id'])?(int)$this->stash['target_user_id']:0;
        $kind = isset($this->stash['kind'])?(int)$this->stash['kind']:1;
        $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:10;
        $user_id = (int)$this->visitor->id;
        $content = $this->stash["content"];
        
		if(empty($target_id)){
            return $this->ajax_notification("缺少必要参数",true);
        }
        if(empty($content)){
            return $this->ajax_notification("你没有输入举报内容",true);
        }
        
        $data = array();
		try{
            $report = new Sher_Core_Model_Report();
            $data['target_id'] = $target_id;
            $data['target_type'] = $target_type;
            $data['target_user_id'] = $target_user_id;
            $data['evt'] = $evt;
            $data['kind'] = $kind;
            $data['user_id'] = $user_id;
            $data['content'] = $content;
            
            $ok = $report->create($data);
            if($ok){
                // skip
            }else{
                return $this->ajax_notification('保存失败',true);       
            }

        }catch(Doggy_Model_ValidateException $e){
            return $this->ajax_notification('操作失败:'.$e->getMessage(),true);
        }
        
		$this->stash['mode'] = 'report';
        
		return $this->to_taconite_page('ajax/send_ok.html');
    }

    /**
     * 获取用户签到数据
     */
    public function ajax_fetch_user_sign(){
        $continuity_times = 0;
        $has_sign = 0;
		// 当前用户是否有权限
        if($this->visitor->id){
            $user_sign_model = new Sher_Core_Model_UserSign();
            $user_sign = $user_sign_model->extend_load((int)$this->visitor->id);

            if($user_sign){
                $today = (int)date('Ymd');
                $yesterday = (int)date('Ymd', strtotime('-1 day'));
                if($user_sign['last_date'] == $yesterday){
                    $continuity_times = $user_sign['sign_times'];
                }elseif($user_sign['last_date'] == $today){
                    $has_sign = 1;
                    $continuity_times = $user_sign['sign_times'];
                }
				
                $result = array('is_true'=>1, 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times, 'data'=>$user_sign);
				
				// 查看前三名签到的用户名单
				$userinfo = array();
				$user_model = new Sher_Core_Model_User();
				$res = $user_sign_model->find(array(),array('field'=>array('_id'),'page'=>1,'size'=>3,'sort'=>array('last_sign_time'=>-1)));
				foreach($res as $k => $v){
					$user_id = $v['_id'];
					$user = $user_model->extend_load((int)$user_id);
					$userinfo[$k] = $user;
				}
				$result['data']['sign'] = $userinfo;
            }else{
                $result = array('is_true'=>0, 'msg'=>'数据不存在', 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times);
            }
        }else{
            $result = array('is_true'=>0, 'msg'=>'未注册', 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times);   
        }
        // 加载签到操作
        $result['is_doing'] = false;
    
        return $this->ajax_json('', false, '', $result);
    }

    /**
     * 用户每日签到
     */
    public function ajax_sign_in(){
        if ($this->visitor->id){
            $user_sign_model = new Sher_Core_Model_UserSign();
            $result = $user_sign_model->sign_in((int)$this->visitor->id, array('user_kind'=>$this->visitor->kind));   
        }else{
            $result = array('is_true'=>0, 'has_sign'=>0, 'msg'=>'没有权限!', 'continuity_times'=>0);    
        }
        // 执行签到操作
        $result['is_doing'] = true;
        
        return $this->ajax_json('', false, '', $result);
    }
	
	/**
     * ajax请求粉丝信息
     */
	public function ajax_follow_list(){
		
		if(!$this->visitor->id){
            return $this->ajax_note('请登录后在操作!',true);
        }
		
		$uid = $this->visitor->id;
		$user = new Sher_Core_Model_User();
		$follow = new Sher_Core_Model_Follow();
		$follow = $follow->find(array('user_id'=>(int)$uid));
		
		$databack = array();
		$url = Doggy_Config::$vars['app.url.user'];
		foreach($follow as $k => $v){
			$userInfo = $user->find_by_id((int)$v['follow_id']);
			$databack[$k]['uid'] = $v['follow_id'];
			$databack[$k]['name'] = $userInfo['nickname'];
			$databack[$k]['url'] = $url.'/'.$v['follow_id'];;
		}
		
		//var_dump($databack);
		echo json_encode($databack);
	}
}
