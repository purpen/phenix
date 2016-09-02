<?php
/**
 * 社区活动
 * @author purpen
 */
class Sher_App_Action_Active extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'cover_id' => 0,
		'page'  => 1,
        'floor' => 0,
		'ref'  => null,
        'category_id' => 0,
        'page_title_suffix' => '太火鸟-智能硬件爱好者活动聚集地',
        'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,智能硬件活动',
        'page_description_suffix' => '太火鸟-智能硬件爱好者活动聚集地，智能硬件校园巡回宣讲，十万火计创意征集大赛，中国智能硬件蛋年创新大会等上百场活动等待你的发起和参与。',
	);
	
	protected $page_tab = 'page_active';
	protected $page_html = 'page/active/index.html';
	
	protected $exclude_method_list = array('execute', 'index', 'get_list', 'view','campaign','ajax_fetch_signup');
	
	public function _init() {
		//$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_sub_active');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_ACTIVE;
  }
	
	/**
	 * 活动
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 社区活动列表
	 */
	public function campaign(){
		return $this->to_html_page('page/active/show_list.html');
	}
	
	/**
	 * 社区活动首页
	 */
	public function index(){
		return $this->to_html_page('page/active/index.html');
	}
	
	/**
	 * 活动列表
	 */
	public function get_list(){

		// 综合分类
		$this->stash['topic_category_official'] = Doggy_Config::$vars['app.topic_category_official'];
		// 产品分类
		$this->stash['topic_category_user'] = Doggy_Config::$vars['app.topic_category_user'];
		
		$pager_url = Sher_Core_Helper_Url::active_list_url($this->stash['category_id']).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('page/active/list.html');
	}

	
	/**
	 * 显示活动详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.active'];
		if(empty($id)){
			return $this->show_message_page('访问的活动不存在！', $redirect_url);
		}
		
		$model = new Sher_Core_Model_Active();
		$active = $model->load($id);
		
		if(empty($active) || $active['deleted']){
			return $this->show_message_page('访问的活动不存在或已被删除！', $redirect_url);
        }

        if($active['state']==0){
 			return $this->show_message_page('该活动已被禁用！', $redirect_url); 
        }

        //加载扩展数据
        $active = $model->extended_model_row($active);

        $this->stash['is_attend'] = false;
        $this->stash['user_info'] = array();
        //验证用户是否已报名
        if ($this->visitor->id){
            $this->stash['user_info'] = &$this->stash['visitor'];
            $this->stash['is_attend'] = $this->check_user_attend($this->visitor->id, $active['_id'], 1);
        }

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = sprintf("%s-太火鸟-智能硬件爱好者活动聚集地", $active['title']);

		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);

    $this->stash['topic_comment_count'] = 0;

        // 评论参数
        if(!empty($active['topic_ids'])){
            $topic_model = new Sher_Core_Model_Topic();
            $topic = $topic_model->load((int)$active['topic_ids'][0]);
            if($topic) $this->stash['topic_comment_count'] = $topic['comment_count'];

            $comment_options = array(
                'comment_target_id' =>  (int)$active['topic_ids'][0],
                'comment_target_user_id' => $active['user_id'],
                'comment_type'  =>  2,
                'comment_pager' =>  Sher_Core_Helper_Url::active_view_url($id, '#p#'),
                //是否显示上传图片/链接
                'comment_show_rich' => 1,
            );
            $this->_comment_param($comment_options);
        }
        
        $this->stash['active'] = $active;

        $this->stash['avatar_loop'] = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,35,35,36,37,38,39,40,41,42,43,44,45,46,47,48);
		
        // 跳转楼层
        $floor = (int)$this->stash['floor'];
        if($floor){
            $new_page = ceil($floor/10);
            $this->stash['page'] = $new_page;
        }
        
		return $this->to_html_page('page/active/show.html');
	}


  /**
   * 用户报名
   */
  public function ajax_attend(){
    $result = array();
    if(!$this->visitor->id){
			return $this->ajax_note('请登录', true);
    }
    if(!isset($this->stash['target_id'])){
			return $this->ajax_note('请求失败,缺少必要参数', true);
    }

    $mode_active = new Sher_Core_Model_Active();
    $active = $mode_active->find_by_id((int)$this->stash['target_id']);
    if(empty($active)){
 			return $this->ajax_note('活动未找到!', true); 
    }

    $max_count = $active['max_number_count'];



    $mode_attend = new Sher_Core_Model_Attend();

    $query['event'] = Sher_Core_Model_Attend::EVENT_ACTIVE;
    $query['target_id'] = $active['_id'];
    $attend_count = $mode_attend->count($query);
    if($attend_count >= $max_count){
  		return $this->ajax_note('名额已满!', true);   
    }

    $is_attend = $mode_attend->check_signup($this->visitor->id, (int)$this->stash['target_id'], 1);
    if($is_attend){
 			return $this->ajax_note('不能重复报名!', true);  
    }

    if(isset($this->stash['is_user_info']) && (int)$this->stash['is_user_info']==1){
      if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['city'])){
 			  return $this->ajax_note('请求失败,缺少用户必要参数', true); 
      }

      $user_data = array();
      $user_data['profile']['realname'] = $this->stash['realname'];
      $user_data['profile']['phone'] = $this->stash['phone'];
      if(!empty($this->stash['address'])){
        $user_data['profile']['address'] = $this->stash['address'];
      }
      if(!empty($this->stash['job'])){
        $user_data['profile']['job'] = $this->stash['job'];
      }
      if(!empty($this->stash['company'])){
        $user_data['profile']['company'] = $this->stash['company'];
      }
      if(!empty($this->stash['industry'])){
        $user_data['profile']['industry'] = $this->stash['industry'];
      }

      if(!empty($this->stash['city'])){
        $user_data['city'] = $this->stash['city'];
      }
      if(!empty($this->stash['email'])){
        $user_data['email'] = $this->stash['email'];
      }

      try {
        //更新基本信息
        $user_ok = $this->visitor->save($user_data);
        if(!$user_ok){
          return $this->ajax_note("更新用户信息失败", true);  
        }
      } catch (Sher_Core_Model_Exception $e) {
        Doggy_Log_Helper::error('Failed to active attend update profile:'.$e->getMessage());
        return $this->ajax_note("更新失败:".$e->getMessage(), true);
      }
    
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = (int)$this->stash['target_id'];
    $data['event'] = 1;
    try{
      $ok = $mode_attend->apply_and_save($data);
      if($ok){
		    return $this->to_taconite_page('ajax/attend_ok.html');
      }else{
  			return $this->ajax_note('报名失败!', true);   
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save active attend failed: ".$e->getMessage());
 			return $this->ajax_note('报名失败.!', true); 
    }
  
  }

  /**
   * 验证用户是否已报名
   */
  protected function check_user_attend($user_id, $target_id, $event=1){
    $mode_attend = new Sher_Core_Model_Attend();
    return $mode_attend->check_signup($user_id, $target_id, $event);
  }

  /**
   * ajax获取报名列表
   */
  public function ajax_fetch_signup(){
    if(!isset($this->stash['target_id'])){
      return false;
    }
    if($this->stash['from']=='site'){
      $this->stash['size'] = 80;
    }else{
      $this->stash['size'] = 30;
    }
    $this->stash['evt'] = isset($this->stash['evt'])?(int)$this->stash['evt']:1;
    return $this->to_taconite_page('ajax/fetch_active_signup.html');
  }

  /**
   * 评论参数
   */
  protected function _comment_param($options){
    $this->stash['comment_target_id'] = $options['comment_target_id'];
    $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
    $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = $options['comment_pager'];

    //是否显示图文并茂
    $this->stash['comment_show_rich'] = $options['comment_show_rich'];
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

}

