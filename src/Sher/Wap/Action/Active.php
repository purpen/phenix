<?php
/**
 * 活动
 * @author purpen
 */
class Sher_Wap_Action_Active extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
    'category_id' => 0,
    'type' => 0,
    'kind' => 0,
    'page_title_suffix' => '太火鸟-智能硬件爱好者活动聚集地',
    'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,智能硬件活动',
    'page_description_suffix' => '太火鸟-智能硬件爱好者活动聚集地，智能硬件校园巡回宣讲，十万火计创意征集大赛，中国智能硬件蛋年创新大会等上百场活动等待你的发起和参与。',
	);
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','getlist','view','ajax_load_list');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		//$this->set_target_css_state('getlist');
		// 获取列表
		$pager_url = Sher_Core_Helper_Url::active_list_url($this->stash['category_id']).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('wap/active_list.html');
	}

	/**
	 * 详情
	 */
	public function view(){
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap.active'];
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

    //手机banner图
    $active['wap_banner'] = null;
    if(!empty($active['wap_banner_id'])){
      $asset_model = new Sher_Core_Model_Asset();
      $banner = $asset_model->extend_load($active['wap_banner_id']);
      if($banner){
        $active['wap_banner'] = $banner;
      }
    }

    $this->stash['is_attend'] = false;
    $this->stash['user_info'] = array();
    //验证用户是否已报名
    if ($this->visitor->id){
      $this->stash['user_info'] = &$this->stash['visitor'];
      $this->stash['is_attend'] = $this->check_user_attend($this->visitor->id, $active['_id'], 1);
    }

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-太火鸟-智能硬件爱好者活动聚集地", $active['title']);

		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);

    //评论参数
    if(!empty($active['topic_ids'])){
      $comment_options = array(
        'comment_target_id' =>  (int)$active['topic_ids'][0],
        'comment_target_user_id' => $active['user_id'],
        'comment_type'  =>  2,
        'comment_pager' =>  Sher_Core_Helper_Url::wap_active_view_url($id, '#p#'),
        //是否显示上传图片/链接
        'comment_show_rich' => 1,
      );
      $this->_comment_param($comment_options);
    }

    $this->stash['active'] = $active;

    // 获取话题评论数
    $this->stash['comment_count'] = 0;
    if(!empty($active['topic_ids'])){
      $topic_model = new Sher_Core_Model_Topic();
      $topic = $topic_model->load((int)$active['topic_ids'][0]);
      if(!empty($topic)){
        $this->stash['comment_count'] = $topic['comment_count'];
      }
    }

		return $this->to_html_page('wap/active_show.html');
	}

  /**
   * 验证用户是否已报名
   */
  protected function check_user_attend($user_id, $target_id, $event=1){
    $mode_attend = new Sher_Core_Model_Attend();
    return $mode_attend->check_signup($user_id, $target_id, $event);
  }

  /**
   * 用户报名
   */
  public function ajax_attend(){
    $this->stash['stat'] = 0;
    $this->stash['msg'] = null;
    $evt = $this->stash['evt'] = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    if(!$this->visitor->id){
      $this->stash['msg'] = '请登录';
			return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }
    if(!isset($this->stash['target_id'])){
      $this->stash['msg'] = '请求失败,缺少必要参数';
			return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }

    $mode_active = new Sher_Core_Model_Active();
    $active = $mode_active->find_by_id((int)$this->stash['target_id']);
    if(empty($active)){
      $this->stash['msg'] = '活动未找到';
			return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }

    $max_count = $active['max_number_count'];

    $mode_attend = new Sher_Core_Model_Attend();

    $query['event'] = Sher_Core_Model_Attend::EVENT_ACTIVE;
    $query['target_id'] = $active['_id'];
    $attend_count = $mode_attend->count($query);
    if($attend_count >= $max_count){
      $this->stash['msg'] = '名额已满';
			return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }

    $is_attend = $mode_attend->check_signup($this->visitor->id, (int)$this->stash['target_id'], 1);
    if($is_attend){
      $this->stash['msg'] = '不能重复报名';
			return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }

    if(isset($this->stash['is_user_info']) && (int)$this->stash['is_user_info']==1){
      if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['city'])){
        $this->stash['msg'] = '请求失败,缺少用户必要参数';
        return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
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
          $this->stash['msg'] = '更新用户信息失败';
          return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
        }
      } catch (Sher_Core_Model_Exception $e) {
        Doggy_Log_Helper::error('Failed to active attend update profile:'.$e->getMessage());
        $this->stash['msg'] = "更新失败:".$e->getMessage();
        return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
      }
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = (int)$this->stash['target_id'];
    $data['event'] = 1;
    try{
      $ok = $mode_attend->apply_and_save($data);
      if($ok){
        $this->stash['stat'] = 1;
        $this->stash['msg'] = '报名成功!';
        return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
      }else{
        $this->stash['msg'] = '报名失败';
        return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save active attend failed: ".$e->getMessage());
      $this->stash['msg'] = '报名失败.!';
      return $this->to_taconite_page('ajax/wap_active_userinfo_show_error.html');
    }
  }

  /**
   * 登录成功后弹出报名窗口
   */
  public function ajax_popup(){
    return $this->to_taconite_page('wap/active/ajax_popup_sign.html');
  }

  /**
   * ajax加载活动列表
   */
  public function ajax_load_list(){        
        $type = $this->stash['type'];
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        $sort = $this->stash['sort'];
        $kind = (int)$this->stash['kind'];
        
        $service = Sher_Core_Service_Active::instance();
        $query = array();
        $options = array();

        $query['step_stat'] = array('$in'=>array(1,2));
        $query['state'] = 1;
        //$query['published'] = 1;
        $query['deleted'] = 0;

        if($kind){
          $query['kind'] = $kind;
        }


		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
        
        $options['page'] = $page;
        $options['size'] = $size;
        
        $result = $service->get_active_list($query, $options);
        //组织数据

        for($i=0;$i<count($result['rows']);$i++){
          if($result['rows'][$i]['end_time']){
            $is_end_time = true;
            $end_time_format = date('Y-m-d', $result['rows'][$i]['end_time']);
          }else{
            $is_end_time = false;
            $end_time_format = null;         
          }
          $result['rows'][$i]['is_end_time'] = $is_end_time;
          $result['rows'][$i]['end_time_format'] = $end_time_format;
          $step_stat = $result['rows'][$i]['step_stat'];
          if($step_stat==1){
            $result['rows'][$i]['is_running'] = true;         
          }elseif($step_stat==2){
            $result['rows'][$i]['is_running'] = false;           
          }

          // 过滤用户表
          if(isset($result['rows'][$i]['user'])){
            $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
          }

        } // end for
        
        $data['type'] = $type;
        $data['page'] = $page;
        $data['sort'] = $sort;
        $data['size'] = $size;
        $data['results'] = $result;
        
        return $this->ajax_json('', false, '', $data);
  }

  /**
   * 评论参数
   */
  protected function _comment_param($options){
        $this->stash['comment_target_id'] = $options['comment_target_id'];
        $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
        $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }
	
}

