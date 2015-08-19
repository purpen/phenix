<?php
/**
 * 新品试用
 * @author purpen
 */
class Sher_Wap_Action_Try extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
    'page_title_suffix' => '新品试用-太火鸟智能硬件孵化平台',
    'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
    'page_description_suffix' => '【免费】申请智能硬件产品试用，发表产品评测，尽在太火鸟智能硬件孵化平台。',
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','getlist','view','apply_success', 'ajax_fetch_rank','tries', 'ajax_load_list');
	
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

	/**
	  *支持
	 */
	public function apply_success(){
    $apply_id = isset($this->stash['apply_id'])?$this->stash['apply_id']:null;
		$redirect_url = Doggy_Config::$vars['app.url.wap.try'];
		if(empty($apply_id)){
			return $this->show_message_page('传入参数不正确！', $redirect_url);
		}
    $apply_model = new Sher_Core_Model_Apply();
    $apply = $apply_model->extend_load($apply_id);
 		if(empty($apply)){
			return $this->show_message_page('不存在的申请名单！', $redirect_url);
    }

    $try_model = new Sher_Core_Model_Try();
    $try = $try_model->extend_load($apply['target_id']);
    if(empty($try)){
 			return $this->show_message_page('试用产品不存在！', $redirect_url);   
    }
    $apply_user_id = $apply['user_id'];
    $is_current_user = false;
    $is_support = false;
    // 是否是当前用户
    if($this->visitor->id){
      if($apply_user_id==(int)$this->visitor->id){
        $is_current_user = true;
      }else{
        // 是否已经支持过
        $attend_model = new Sher_Core_Model_Attend();
        $is_support = $attend_model->check_signup($this->visitor->id, (string)$apply['_id'], Sher_Core_Model_Attend::EVENT_APPLY);
        if($is_support){
          $this->stash['is_support'] = true;
        }
      }
    }

    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);


    $this->stash['is_current_user'] = $is_current_user;
    $this->stash['apply'] = $apply;
    $this->stash['try'] = $try;

    // 69电臀标识
    if($try['_id']==52019){
      $is_69 = true;
    }else{
      $is_69 = false;
    }
    $this->stash['is_69'] = $is_69;

		return $this->to_html_page('wap/try_success.html');
	}
	
	/**
	 * 首页
	 */
	public function getlist(){
		return $this->to_html_page('wap/trylist.html');
	}
	
	/**
	 * 查看评测
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		$tpl = 'wap/try_show.html';
		
		$redirect_url = Doggy_Config::$vars['app.url.wap.try'];
		if(empty($id)){
			return $this->show_message_page('访问的公测产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Try();
		$try = &$model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-新品试用-太火鸟智能硬件孵化平台", $try['title']);
    if(!empty($try['tags'])){
      $this->stash['page_keywords_suffix'] = sprintf("太火鸟,智能硬件,智能硬件孵化平台,新品试用,%s,产品评测", $try['tags'][0]);   
    }
    $this->stash['page_description_suffix'] = sprintf("【免费】申请%s试用，发表产品评测，更多智能硬件使用，就在太火鸟智能硬件孵化平台。", $try['short_title']);
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['try'] = &$try;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];

    $apply_model = new Sher_Core_Model_Apply();

    // 当前用户是否申请过
    $is_applied = false;
    if($this->visitor->id){
      $has_one_apply = $apply_model->first(array('target_id'=>$try['_id'], 'user_id'=>$this->visitor->id));
      if(!empty($has_one_apply)){
        $is_applied = true;
        $this->stash['apply'] = $has_one_apply;
      }
    }

    $this->stash['is_applied'] = $is_applied;

    //评论参数
    $comment_options = array(
      'comment_target_id' =>  $try['_id'],
      'comment_target_user_id' =>  $try['user_id'],
      'comment_type'  =>  3,
      //是否显示上传图片/链接
      'comment_show_rich' => 1,
    );
    $this->_comment_param($comment_options);

    // 69电臀标识
    if($try['_id']==52019){
      $is_69 = true;
    }else{
      $is_69 = false;
    }
    $this->stash['is_69'] = $is_69;
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 提交申请
	 */
	public function ajax_apply(){
    $this->stash['stat'] = 0;
    $this->stash['msg'] = null;
		if (!isset($this->stash['target_id'])){
      $this->stash['msg'] = '缺少请求参数';
			return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
		}
		
		$target_id = $this->stash['target_id'];
		$user_id = $this->visitor->id;
		
		try{
			// 验证是否结束
			$try = new Sher_Core_Model_Try();
			$row = $try->extend_load((int)$target_id);
			if($row['is_end']){
        $this->stash['msg'] = '抱歉，活动已结束，等待下次再来！';
			  return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
			}
			
			// 检测是否已提交过申请
			$model = new Sher_Core_Model_Apply();
			if(!$model->check_reapply($user_id,$target_id)){
        $this->stash['msg'] = '你已提交过申请，无需重复提交！';
			  return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
			}
			
			if(empty($this->stash['_id'])){
				if(isset($this->stash['id'])){
					unset($this->stash['id']);
				}
				$this->stash['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($this->stash);
        if(!$ok){
          $this->stash['msg'] = '提交失败，请重试！';
          return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');       
        }
        $apply = $model->get_data();
        $this->stash['apply'] = $apply;
        $this->stash['try'] = $row;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
      $this->stash['msg'] = '提交失败，请重试！';
      return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
		}
		$this->stash['stat'] = 1;
    $this->stash['msg'] = '申请提交成功，等待审核.';
    return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
	}

  /**
   * ajax 支持拉票
   */
  public function ajax_support(){
    $apply_id = isset($this->stash['apply_id'])?$this->stash['apply_id']:0;
    if(empty($apply_id)){
  	  return $this->ajax_note('缺少请求参数！', true);      
    }

    $apply_model = new Sher_Core_Model_Apply();
    $apply = $apply_model->load($apply_id);
    if(empty($apply)){
   	  return $this->ajax_note('申请信息不存在！', true);     
    }
    
    $this->stash['try_wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.try.view'], $apply['target_id']);

    $attend_model = new Sher_Core_Model_Attend();
    $is_attend = $attend_model->check_signup($this->visitor->id, $apply_id, Sher_Core_Model_Attend::EVENT_APPLY);
    if($is_attend){
   	  return $this->ajax_note('您已经支持过了！', true);     
    }

    $data = array(
      'user_id' => (int)$this->visitor->id,
      'target_id' => $apply_id,
      'event' => Sher_Core_Model_Attend::EVENT_APPLY,
    );

    $this->stash['success'] = false;
    try{
      $ok = $attend_model->apply_and_save($data);
      if($ok){
        $this->stash['success'] = true;
      }
    }catch(Sher_Core_Model_Exception $e){
      Doggy_Log_Helper::warn("Save try apply attend failed: ".$e->getMessage());
    }

    return $this->to_taconite_page('wap/try/ajax_support.html');
  }

  /**
   * ajax 获取拉票排行
   */
  public function ajax_fetch_rank(){
    $apply_id = isset($this->stash['apply_id'])?$this->stash['apply_id']:null;
		if(empty($apply_id)){
			return $this->ajax_note('传入参数不正确！', true);
		}
    $apply_model = new Sher_Core_Model_Apply();
    $apply = $apply_model->extend_load($apply_id);
		if(empty($apply)){
			return $this->ajax_note('申请信息不存在！', true);
		}

    //获取当前用户排行,笨方法
    $page = 1;
    $size = 200;
    $is_end = false;
    $total = 0;
    while(!$is_end){
      $query = array('target_id'=>$apply['target_id'], 'type'=>Sher_Core_Model_Apply::TYPE_TRY);
      $options = array('page'=>$page,'size'=>$size,'sort'=>array('vote_count'=>-1));
      $list = $apply_model->find($query, $options);
      if(empty($list)){
        break;
      }
      $max = count($list);
      for ($i=0; $i < $max; $i++) {
        $total++;
        if($list[$i]['user_id']==$apply['user_id']){
          break;
        }
      }
      if($max < $size){
        break;
      }
      $page++;
    }

    //获取拉票前10统计
    $vote_count_top = 0;
    $query = array('target_id'=>$apply['target_id'], 'type'=>Sher_Core_Model_Apply::TYPE_TRY);
    $options = array('page'=>1,'size'=>10,'sort'=>array('vote_count'=>-1));
    $apply_top = $apply_model->find($query, $options);
    //前10总票数
    foreach($apply_top as $k=>$v){
      $vote_count_top += (int)$v['vote_count'];
    }
    //插入百分比
    $user_model = new Sher_Core_Model_User();
    foreach($apply_top as $k=>$v){
      $user = $user_model->extend_load($v['user_id']);
      $apply_top[$k]['user'] = $user;
      $apply_top[$k]['percent'] = (int)(($v['vote_count']/(float)$vote_count_top)*100);
    }

    $this->stash['apply'] = $apply;
    $this->stash['rank_no'] = $total;
    $this->stash['apply_top'] = $apply_top;
  
    return $this->to_taconite_page('wap/try/ajax_rank_box.html');
  }

  /**
   * ajax加载试用列表
   */
  public function ajax_load_list(){        
        $type = $this->stash['type'];
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        $sort = $this->stash['sort'];
        
        $service = Sher_Core_Service_Try::instance();
        $query = array();
        $options = array();

        $query['state'] = 1;

				$options['sort_field'] = 'latest';
        
        $options['page'] = $page;
        $options['size'] = $size;
        
        $result = $service->get_try_list($query, $options);
        //组织数据

        for($i=0;$i<count($result['rows']);$i++){
          $step_stat = isset($result['rows'][$i]['step_stat']) ? $result['rows'][$i]['step_stat'] : 0;
          $result['rows'][$i]['step_ing'] = $result['rows'][$i]['step_verify'] = $result['rows'][$i]['step_recover'] = $result['rows'][$i]['step_no'] = $result['rows'][$i]['step_over'] = false;
          switch($step_stat){
            case 1: //进行中
              $result['rows'][$i]['step_ing'] = true;
              break;
            case 2: // 审核中
              $result['rows'][$i]['step_verify'] = true;
              break;
            case 3: // 报告回收
              $result['rows'][$i]['step_recover'] = true;
              break;
            case 4: // 未定义
              $result['rows'][$i]['step_no'] = true;
              break;
            case 5://结束
              $result['rows'][$i]['step_over'] = true;
              break;
          }

        }//endfor
        
        $this->stash['results'] = $result;
        
        return $this->ajax_json('', false, '', $this->stash);
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

    //是否显示图文并茂
    $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

}

