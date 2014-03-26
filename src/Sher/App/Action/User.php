<?php
/**
 * 个人主页
 */
class Sher_App_Action_User extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'user_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/my.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
        $user_id = $this->stash['user_id'];
        $this->stash['user'] = null;
        if (!empty($user_id)) {
            $this->stash['user'] = &DoggyX_Model_Mapper::load_model((int)$user_id,'Sher_Core_Model_User');
        }
    }

	/**
	 * 
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
		// 首次登录，需先完成资料
		if($this->stash['visitor']['first_login'] == 1){
			$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile?first_login=1';
			return $this->to_redirect($user_profile_url);
		}
		$this->set_target_css_state('home');
		$this->stash['profile'] = $this->stash['user']['profile'];
		$this->stash['reverse_sex'] = ($this->stash['user']['sex'] == Sher_Core_Model_User::SEX_MALE) ? Sher_Core_Model_User::SEX_FEMALE : Sher_Core_Model_User::SEX_MALE;
		
		return $this->display_tab_page('tab_home');
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
	
	
}
?>