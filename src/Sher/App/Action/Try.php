<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_App_Action_Try extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
    'floor' => 0,
    'step' => 0,
		'page_title_suffix' => '新品试用-太火鸟智能硬件孵化平台',
		'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
		'page_description_suffix' => '【免费】申请智能硬件产品试用，发表产品评测，尽在太火鸟智能硬件孵化平台。',
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array('execute','get_list','view','trylist');
	
	public function _init() {
		$this->set_target_css_state('page_sub_try');
    }
	
	/**
	 * 列表
	 */
	public function execute(){
		return $this->get_list();
		# return $this->get_list2();
	}
	
	/**
	 * 列表
	 */
	public function trylist(){
		$this->set_target_css_state('page_try');
    $pager_url = sprintf("%s/trylist?step=%d&page=#p#", Doggy_Config::$vars['app.url.try'], (int)$this->stash['step']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('page/try/trylist.html');
	}
	
	/**
	 * 评测列表
	 */
	public function get_list(){
		$this->set_target_css_state('page_try');

    /**
    // 记录其它地过来用户注册统计
    if(isset($this->stash['from'])){
      $from = (int)$this->stash['from'];
      // 统计点击数量
      $dig_model = new Sher_Core_Model_DigList();
      $dig_key = Sher_Core_Util_Constant::DIG_THIRD_SITE_STAT;

      $dig = $dig_model->load($dig_key);
      if(empty($dig) || !isset($dig['items']["stat_$from"])){
        $dig_model->update_set($dig_key, array("items.stat_$from"=>1), true);     
      }else{
        // 增加浏览量
        $dig_model->inc($dig_key, "items.stat_$from", 1);
      }

      // 存cookie
      @setcookie('from_origin', $from, time()+3600*24, '/');
      $_COOKIE['from_origin'] = $from;

    }
    **/

        $pager_url = sprintf("%s/list-c%d-t%d-s%d-p%s", Doggy_Config::$vars['app.url.try'], 0, 0, 0, '#p#');
		
		$this->stash['pager_url'] = $pager_url;
        
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];
		
		return $this->to_html_page('page/try/list.html');
	}

	/**
    	 * 评测列表
    	 */
    	public function get_list2(){
    		$this->set_target_css_state('page_try');

        /**
        // 记录其它地过来用户注册统计
        if(isset($this->stash['from'])){
          $from = (int)$this->stash['from'];
          // 统计点击数量
          $dig_model = new Sher_Core_Model_DigList();
          $dig_key = Sher_Core_Util_Constant::DIG_THIRD_SITE_STAT;

          $dig = $dig_model->load($dig_key);
          if(empty($dig) || !isset($dig['items']["stat_$from"])){
            $dig_model->update_set($dig_key, array("items.stat_$from"=>1), true);
          }else{
            // 增加浏览量
            $dig_model->inc($dig_key, "items.stat_$from", 1);
          }

          // 存cookie
          @setcookie('from_origin', $from, time()+3600*24, '/');
          $_COOKIE['from_origin'] = $from;

        }
        **/

            $pager_url = sprintf("%s/list-c%d-t%d-s%d-p%s", Doggy_Config::$vars['app.url.try'], 0, 0, 0, '#p#');

    		$this->stash['pager_url'] = $pager_url;

    		// 评测报告分类
    		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];

    		return $this->to_html_page('page/try/list2.html');
    	}
	
	/**
	 * 查看评测
	 */
	public function view(){
    $this->set_target_css_state('page_try');
		$id = (int)$this->stash['id'];
		$tpl = 'page/try/view.html';
		
		$redirect_url = Doggy_Config::$vars['app.url.try'];
		if(empty($id)){
			return $this->show_message_page('访问的试用产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Try();
		$try = &$model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}

        // 不可申请状态
    $this->stash['cannot_apply'] = false;
    // 是否已想要
    $this->stash['is_want'] = false;
    if($try['step_stat']==0){
      $this->stash['cannot_apply'] = true;
      if($this->visitor->id){
        $attend_model = new Sher_Core_Model_Attend();
        $is_want = $attend_model->check_signup($this->visitor->id, $try['_id'], Sher_Core_Model_Attend::EVENT_TRY_WANT);
        if($is_want) $this->stash['is_want'] = true;
      }
        
    }

    // 加载配图
    $img_asset = array();
    if(!empty($try['imgs'])){
        $asset_model = new Sher_Core_Model_Asset();
        foreach($try['imgs'] as $k=>$v){
            if(!empty($v)){
                $asset = $asset_model->extend_load($v);
                if($asset){
                    $img_asset[$k] = $asset;
                }
            }
        }
    }

    // 添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-新品试用-太火鸟智能硬件孵化平台", $try['title']);
    if(!empty($try['tags'])){
        $this->stash['page_keywords_suffix'] = sprintf("太火鸟,智能硬件,智能硬件孵化平台,新品试用,%s,产品评测", $try['tags'][0]);   
    }
    $this->stash['page_description_suffix'] = sprintf("【免费】申请%s试用，发表产品评测，更多智能硬件使用，就在太火鸟智能硬件孵化平台。", $try['short_title']);
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);

		$model->increase_counter('true_view_count', 1, $id);
		$model->increase_counter('web_view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }

		// 当前用户是否申请过
		$is_applied = false;
		if($this->visitor->id){
            $apply_model = new Sher_Core_Model_Apply();
            $has_one_apply = $apply_model->first(array('target_id'=>$try['_id'], 'user_id'=>$this->visitor->id));
            if(!empty($has_one_apply)){
                $is_applied = true;
                $has_one_apply = $apply_model->extended_model_row($has_one_apply);
                $this->stash['apply'] = $has_one_apply;
            }
        }

    // 是否含有产品参数
    if((isset($try['price']) && !empty($try['price'])) || (isset($try['buy_url']) && !empty($try['buy_url'])) ){
      $this->stash['has_product_parameter'] = true;
    }else{
      $this->stash['has_product_parameter'] = false;   
    }
		
		$this->stash['try'] = &$try;
        $this->stash['is_applied'] = $is_applied;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;

        $this->stash['img_asset'] = $img_asset;
		
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];

		//评论参数
		$comment_options = array(
		  'comment_target_id' =>  $try['_id'],
		  'comment_target_user_id' => $try['user_id'],
		  'comment_type'  =>  3,
		  'comment_pager' =>  sprintf(Doggy_Config::$vars['app.url.try.comment'], $try['_id'], "#p#"),
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);
        
        // 跳转楼层
        $floor = (int)$this->stash['floor'];
        if($floor){
            $new_page = ceil($floor/10);
            $this->stash['page'] = $new_page;
        }
		
		return $this->to_html_page($tpl);
    }
	
	/**
	 * 提交申请
	 */
	public function ajax_apply(){
		if (!isset($this->stash['target_id'])){
			return $this->ajax_modal('缺少请求参数！', true);
		}
		
		$target_id = $this->stash['target_id'];
		$user_id = $this->visitor->id;
		
		try{
			// 验证是否结束
			$try = new Sher_Core_Model_Try();
			$row = $try->extend_load((int)$target_id);

      // 预热状态不可申请
			if($row['step_stat']==0){
				return $this->ajax_modal('预热中是不能申请的！', true);
			}
			if($row['is_end']){
				return $this->ajax_modal('抱歉，活动已结束，等待下次再来！', true);
			}

      // 验证是否加入黑名单(未提交报告用户)
      if(Sher_Core_Helper_Try::check_try_apply_blacklist($user_id)){
 				return $this->ajax_modal('您的账户已被列入试用黑名单，请联系太火鸟社区组!', true);     
      }

      // 是否符合申请条件
      /**
      if(isset($row['apply_term']) && !empty($row['apply_term'])){
        if($row['apply_term']==1){  // 等级
          $user_model = new Sher_Core_Model_User();
          $user = $user_model->extend_load((int)$user_id);
          if((int)$user['ext_state']['rank_id'] < (int)$row['term_count']){
            return $this->ajax_modal('您的等级不能申请当前试用产品！', true);
          }
        }elseif($row['apply_term']==2){ // 鸟币
          // 用户实时积分
          $point_model = new Sher_Core_Model_UserPointBalance();
          $current_point = $point_model->load((int)$user_id);
          if($current_point['balance']['money'] < (int)$row['term_count']){
            return $this->ajax_modal('您的鸟币数量不足，不能申请当前试用产品！', true);         
          }
        }
        
      }
      **/
			
			// 检测是否已提交过申请
			$model = new Sher_Core_Model_Apply();
			if(!$model->check_reapply($user_id,$target_id)){
				return $this->ajax_modal('你已提交过申请，无需重复提交！', true);
			}
			
			if(empty($this->stash['_id'])){
				if(isset($this->stash['id'])){
					unset($this->stash['id']);
				}
				$this->stash['user_id'] = $user_id;
				
				$nickname = $this->visitor->nickname;
				$this->stash['nickname'] = $nickname;
				
        $this->stash['ip'] = Sher_Core_Helper_Auth::get_ip();
				$ok = $model->apply_and_save($this->stash);
        if($ok){

          $user_data = array();
          if(empty($this->visitor->profile->realname)){
            $user_data['profile.realname'] = isset($this->stash['name']) ? $this->stash['name'] : null;
          }
          if(empty($this->visitor->profile->phone)){
            $user_data['profile.phone'] = isset($this->stash['phone']) ? $this->stash['phone'] : null;
          }
          if(empty($this->visitor->profile->address)){
            $user_data['profile.address'] = isset($this->stash['address']) ? $this->stash['address'] : null;
          }
          if(empty($this->visitor->profile->zip)){
            $user_data['profile.zip'] = isset($this->stash['zip']) ? $this->stash['zip'] : null;
          }
          if(empty($this->visitor->profile->weixin)){
            $user_data['profile.weixin'] = isset($this->stash['wx']) ? $this->stash['wx'] : null;
          }
          if(empty($this->visitor->profile->im_qq)){
            $user_data['profile.im_qq'] = isset($this->stash['qq']) ? $this->stash['qq'] : null;
          }
          if(empty($this->visitor->profile->province_id)){
            $user_data['profile.province_id'] = isset($this->stash['province']) ? (int)$this->stash['province'] : 0;
          }
          if(empty($this->visitor->profile->district_id)){
            $user_data['profile.district_id'] = isset($this->stash['district']) ? (int)$this->stash['district'] : 0;
          }

          //更新基本信息
          $this->visitor->update_set($this->visitor->id, $user_data);

          $this->stash['apply_id'] = $model->id;
          $apply = $model->extend_load((string)$model->id);       
        }else{
          $apply = null;
        }
        $this->stash['apply'] = $apply;

			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->ajax_modal('提交失败，请重试！', true);
		}
		$this->stash['is_try'] = true;
		return $this->to_taconite_page('ajax/attend_ok.html');
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


  /**
   * 预热预约提醒
   *
   */
  public function want_attend(){
    $try_id = isset($this->stash['try_id']) ? (int)$this->stash['try_id'] : 0;
    if(empty($try_id)){
      return $this->ajax_json('缺少请求参数!', true);
    }
		$try_model = new Sher_Core_Model_Try();
		$try = $try_model->load($try_id);
    if(empty($try)){
      return $this->ajax_json('试用产品不存在!', true);
    }
    if($try['step_stat'] != 0){
      return $this->ajax_json('不是预热状态!', true);
    }
    
    $attend_model = new Sher_Core_Model_Attend();
    $is_want = $attend_model->check_signup($this->visitor->id, $try_id, Sher_Core_Model_Attend::EVENT_TRY_WANT);
    if($is_want){
      return $this->ajax_json('已经预约过!', true);
    }

    $data = array(
      'user_id' => $this->visitor->id,
      'target_id' => $try_id,
      'event'  => Sher_Core_Model_Attend::EVENT_TRY_WANT,
    );

    $ok = $attend_model->create($data);
    if($ok){
      return $this->ajax_json('操作成功!', false, '', $data);
    }else{
      return $this->ajax_json('操作失败！', true);   
    }
  
  }
	
}
