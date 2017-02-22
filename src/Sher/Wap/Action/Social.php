<?php
/**
 * 移动社区
 * @author purpen
 */
class Sher_Wap_Action_Social extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'category_id' => 0,
		'type' => 1,
		'sort' => 0,
		'page_title_suffix' => '太火鸟话题-智能硬件孵化社区',
		'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
		'page_description_suffix' => '太火鸟话题是国内最大的智能硬件社区，包括智创学堂，孵化需求，活动动态，品牌专区，产品评测等几大社区板块以及上千个智能硬件话题，太火鸟话题-创意与创意的碰撞。',
	);
	
	protected $exclude_method_list = array('execute','dream', 'dream2', 'topic', 'allist', 'allist2', 'get_list', 'show', 'ajax_guess_topics', 'ajax_topic_list', 'ajax_fetch_more');
	
	/**
	 * 社区入口
	 */
	public function execute(){
		return $this->index();
	}

  /**
   * 社区首页
   */
  public function index(){
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $this->stash['type'] = $type;

    switch($type){
      case 1:
        $this->stash['type_stick_css'] = 'active';
        break;
      case 2:
        $this->stash['type_fine_css'] = 'active';
        break;
      case 3:
        $this->stash['type_active_css'] = 'active';
        break;
      case 4:
        $this->stash['type_last_css'] = 'active';
        break;
      case 5:
        $this->stash['type_ce_css'] = 'active';
        break;
      default:
        $this->stash['type'] = 1;
        $this->stash['type_stick_css'] = 'active';
    }
 		
		return $this->to_html_page('wap/topic/home.html'); 
  }
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match.html');
	}

	/**
	 * 十万火计 第２季
	 */
	public function dream2(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		$this->stash['start_time'] = mktime(0,0,0,2,10,2015);
		$this->stash['end_time'] = mktime(23,59,59,6,20,2015);
		
		return $this->to_html_page('wap/match2.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'dream', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match_list.html');
	}

	/**
	 * 全部创意列表 第２季
	 */
	public function allist2(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'dream', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match_list2.html');
	}
	
	/**
	 * 社区首页
	 */
	public function topic(){
    return $this->get_list();
		$prefix_url = Doggy_Config::$vars['app.url.wap.social'].'/c';
		$this->stash['category_prefix_url'] = $prefix_url;
		
		
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		
		if($cid){
			$category = new Sher_Core_Model_Category();
			$child = $category->load((int)$cid);
			if(empty($child)){
				return $this->show_message_page('请选择某个分类');
			}
			$this->stash['child'] = $child;
		}
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.social', 'c'.$cid).$page;
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('wap/topic/index.html');
	}
	
	/**
	 * 全部话题列表
	 */
	public function get_list(){
		$this->set_target_css_state('getlist');
		$category_id = $this->stash['category_id'];
		
		return $this->to_html_page('wap/topic_list.html');
	}

	/**
	 * ajax话题列表
	 */
	public function ajax_topic_list(){
		$category_id = $this->stash['category_id'];
    $this->stash['size'] = isset($this->stash['size']) ? (int)$this->stash['size'] : 20;
    $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 获取某类别列表
    if($category_id){
      $category = new Sher_Core_Model_Category();
      $child = $category->extend_load((int)$category_id);
      if(empty($child)){
        return $this->show_message_page('分类不存在');
      }
      //根据分类ID,显示描述信息
      $this->stash['category_desc'] = Sher_Core_Helper_View::category_desc_show($category_id);

      //添加网站meta标签
      $this->stash['page_title_suffix'] = Sher_Core_Helper_View::meta_category_obj($child, 1);
      $this->stash['page_keywords_suffix'] = Sher_Core_Helper_View::meta_category_obj($child, 2);   
      $this->stash['page_description_suffix'] = Sher_Core_Helper_View::meta_category_obj($child, 3);
      $this->stash['child'] = $child;
      $this->stash['child_id'] = $child['_id'];  
    }else{
      $this->stash['child'] = null;
      $this->stash['child_id'] = 0; 
    }
		
		return $this->to_taconite_page('wap/topic/ajax_topic_list.html');
	}
	
	/**
	 * 显示主题详情帖
	 */
	public function show(){
		
		$id = (int)$this->stash['id'];
		$redirect_url = Doggy_Config::$vars['app.url.wap.social.list'];
		if(empty($id)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}

    // 记录兑吧来的用户，统计注册量用
    if(isset($this->stash['from']) && (string)$this->stash['from']=='db' && $id==112849){
      // 存cookie
      @setcookie('from_origin', '2', time()+3600, '/');
      $_COOKIE['from_origin'] = '2';
      @setcookie('from_target_id', '5', time()+3600, '/');
      $_COOKIE['from_target_id'] = '5';

      // 统计点击数量
      $dig_model = new Sher_Core_Model_DigList();
      $dig_key = Sher_Core_Util_Constant::DIG_THIRD_DB_STAT;

      $dig = $dig_model->load($dig_key);
      if(empty($dig) || !isset($dig['items']["view_05"])){
        $dig_model->update_set($dig_key, array("items.view_05"=>1), true);     
      }else{
        // 增加浏览量
        $dig_model->inc($dig_key, "items.view_05", 1);
      }
      
    }
		
		// 是否允许编辑
		$editable = false;
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->show_message_page('访问的主题不存在或已被删除！', $redirect_url);
		}
        if (!empty($topic)) {
            $topic = $model->extended_model_row($topic);
        }

		//添加网站meta标签
		$this->stash['page_title_suffix'] = sprintf("%s-太火鸟智能硬件", $topic['title']);
		if(!empty($topic['tags'])){
		  $this->stash['page_keywords_suffix'] = sprintf("智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,%s", $topic['tags'][0]);   
		}
		$this->stash['page_description_suffix'] = sprintf("【太火鸟话题】 %s", mb_substr($topic['strip_description'], 0, 20));
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->increase_counter('view_count', $inc_ran, $id);

		$model->increase_counter('true_view_count', 1, $id);
		$model->increase_counter('wap_view_count', 1, $id);
		
		// 当前用户是否有管理权限
		if ($this->visitor->id){
			if ($this->visitor->id == $topic['user_id'] || $this->visitor->can_admin){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$topic['fid']);
		
		$this->stash['topic'] = &$topic;
		$this->stash['parent_category'] = $parent_category;
		$this->stash['editable'] = $editable;
		
		// 是否参赛作品
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		if($topic['category_id'] == $this->stash['dream_category_id']){
			if($topic['created_on'] >= mktime(0,0,0,10,28,2014) && $topic['created_on'] <= mktime(23,59,59,12,20,2014)){
				$this->stash['is_match_idea'] = true;
			}
		}

		//评论参数
		$comment_options = array(
		  'comment_target_id' =>  $topic['_id'],
		  'comment_target_user_id' => $topic['user_id'],
		  'comment_type'  =>  2,
		  'comment_pager' =>  Sher_Core_Helper_Url::topic_view_url($id, '#p#'),
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);

		//微信分享
		$this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
		$timestamp = $this->stash['timestamp'] = time();
		$wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
		$wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
		$url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
		$wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
		$this->stash['wxSha1'] = sha1($wxOri);
		
		// 投票部分代码
		$is_vote = 0;
		if(isset($topic['vote_id']) && !empty($topic['vote_id'])){
			$model_vote = new Sher_Core_Model_Vote();
			$voteOne = $model_vote->find_by_id(array('relate_id' => (int)$id));
			$vote_id = $voteOne['_id'];
			$vote = $model_vote->statistics((int)$vote_id);
			$this->stash['vote'] = &$vote;
			if($vote){
				$is_vote = 1;
			}
		}
		
		$can_vote = 0;
		if(isset($topic['vote_id']) && !empty($topic['vote_id'])){
			$model = new Sher_Core_Model_VoteRecord();
			$data = array();
			$data['vote_id'] = $topic['vote_id'];
			$data['user_id'] = $this->visitor->id;
			$data['relate_id'] = (int)$id;
			$voteRecord = $model->find($data);
			if(count($voteRecord)){
				$can_vote = 1;
			}
		}
		
		// 添加显示权限(登陆状态、发帖本人、星级会员)
		$vote_show = 0;
		if($this->visitor->id && (int)$this->visitor->id == (int)$topic['user_id'] && $this->visitor->mentor){
			$vote_show = 1;
		}
		
		$this->stash['is_vote'] = $is_vote;
		$this->stash['is_vote'] = $is_vote;
		$this->stash['can_vote'] = $can_vote;
		
		return $this->to_html_page('wap/show.html');
	}
	
	/**
	 * 提交话题
	 */
	public function submit(){

		// 评测对象
		if(isset($this->stash['tid'])){
			$this->stash['try_id'] = $this->stash['tid'];
		}
		
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
    $this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		// 判断来源
	  $page_title = '发表话题';
		
		$this->stash['page_title'] = $page_title;
		
		return $this->to_html_page('wap/topic/submit.html');
	}
	
	/**
	 * 保存话题或话题信息
	 */
	public function save(){
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}
		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;

    // 用户发表频率、次数限制
    if(empty($id)){
      if(empty($this->visitor->quality)){
        $pub_is_limit = Sher_Core_Helper_Util::report_filter_limit($this->visitor->id, 1);
        if($pub_is_limit['success']){
          return $this->ajax_json($pub_is_limit['msg'], true);   
        }     
      }
    }
		
		$mode = 'create';
		$data = array();
		
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		$data['tags'] = $this->stash['tags'];
		
		$data['category_id'] = $this->stash['category_id'];
		$data['cover_id'] = $this->stash['cover_id'];
		$data['try_id'] = $this->stash['try_id'];
		
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				$topic = $model->get_data();
				
				$id = $topic['_id'];
				
				// 更新用户主题数量
				$this->visitor->inc_counter('topic_count', $data['user_id']);
				
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("保存失败：".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Sher_Core_Helper_Url::wap_topic_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	
	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_assets() {
		$assets_ids = $this->stash['assets'];
		$asset_type = $this->stash['asset_type'];
		$asset_domain = $this->stash['asset_domain'];
		
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response('ajax/check_upload_assets.html', $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
        return $this->to_taconite_page('ajax/check_upload_assets.html');
    }

	/**
	 * 获取相关话题
	 */
	public function ajax_guess_topics(){
		$sword = $this->stash['sword'];
        $current_id = $this->stash['id'];
		$size = $this->stash['size'];
        
		$result = array();
		$options = array(
			'page' => 1,
			'size' => $size,
			'sort_field' => 'latest',
      'evt' => 'tag',
      't' => 2,
      'oid' => $current_id,
      'type' => 1,
		);
        
		if(!empty($sword)){
      $xun_arr = Sher_Core_Util_XunSearch::search($sword, $options);
      if($xun_arr['success'] && !empty($xun_arr['data'])){
        $topic_mode = new Sher_Core_Model_Topic();
        $items = array();
        foreach($xun_arr['data'] as $k=>$v){
          $topic = $topic_mode->extend_load((int)$v['oid']);
          if(!empty($topic)){
            // 过滤用户表
            if(isset($topic['user'])){
              $topic['user'] = Sher_Core_Helper_FilterFields::user_list($topic['user']);
            }
            if(isset($topic['last_user'])){
              $topic['last_user'] = Sher_Core_Helper_FilterFields::user_list($topic['last_user']);
            }
            array_push($items, array('topic'=>$topic));
          }
        }
        $result['rows'] = $items;
        $result['total_rows'] = $xun_arr['total_count'];
      }else{
        $addition_criteria = array(
            'type' => 2,
            'target_id' => array('$ne' => (int)$current_id),
        );
        $sword = array_values(array_unique(preg_split('/[,，\s]+/u', $sword)));
        //$result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
        $result = array();
      }

    }

    if(empty($result)){
      return;
    }
		$this->stash['result'] = $result;
		
		return $this->to_taconite_page('ajax/guess_topics_wap.html');
	}
	
	/**
	 * 批量更新附件所属
	 */
	protected function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
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
	 * 保存投票信息
	 */
	public function save_vote(){
		
		$back = array(0,0,0);
		$field_name = 'nums';
		
		$vote = json_decode('['.$this->stash['vote'].']',true);
		$vote = $vote[0];
		
		// 验证拒绝重复投票
		$model_vote_record = new Sher_Core_Model_VoteRecord();
		$res_vote_record = $model_vote_record->find(array('vote_id' => (int)$vote['vote_id'],'user_id' => (int)$vote['user_id'],'relate_id' => (int)$vote['topic_id']));
		if($res_vote_record){
			echo 1;exit;
		}
		
		$problem = json_decode('['.$this->stash['problem'].']',true);
		$problem = $problem[0];
		
		// 更新投票次数
		$model_vote = new Sher_Core_Model_Vote();
		if($model_vote->inc_counter($field_name, (int)$vote['vote_id'], $inc=1)){
			$back[0] = 1;
		}
		
		$vote_record = array();
		$i = 0;
		$model_answer = new Sher_Core_Model_Answer();
		foreach($problem as $k => $v){
			foreach($v["answer"] as $key => $value){
				$vote_record[$i]['vote_id'] = (int)$vote['vote_id'];
				$vote_record[$i]['user_id'] = (int)$vote['user_id'];
				$vote_record[$i]['relate_id'] = (int)$vote['topic_id'];
				$vote_record[$i]['problem_id'] = $v['id'];
				$vote_record[$i]['answer_id'] = $value;
				// 更新答案次数
				if($model_answer->inc_counter('nums', $value, $inc=1)){
					$back[1]++;
				}
				$i++;
			}
			$i++;
		}
		
		// 添加投票信息记录
		foreach($vote_record as $v){
			if($model_vote_record->create($v)){
				$back[2]++;
			}
		}
		
		if(!$back[0] || $back[1] !== count($vote_record) || $back[2] !== count($vote_record)){
			echo 0;exit;
		}
		
		$model = new Sher_Core_Model_Vote();
		$result = $model->statistics((int)$vote['vote_id']);
		echo json_encode($result);
	}

  /**
   * 自动加载获取
   */
  public function ajax_fetch_more(){
        
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 15;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		$target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
        
        $query = array();
        $query['published'] = 1;
        // 是评测
        if($type==5){
          $query['try_id'] = array('$ne'=>0);
        }
        // 活动
        if($type==3){
          $query['attrbute'] = Sher_Core_Model_Topic::ATTR_ACTIVE;
        }

        // 产品评测
        if(!empty($target_id)){
          $query['target_id'] = $target_id;
        }
        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
        switch ((int)$sort) {
          case 0:
            $options['sort_field'] = 'latest';
            break;
          case 1:
            $options['sort_field'] = 'update';
            break;
          case 2:
            $options['sort_field'] = 'comment';
            break;
          case 3:
            $options['sort_field'] = 'favorite';
            break;
          case 4:
            $options['sort_field'] = 'love';
            break;
          case 5:
            $options['sort_field'] = 'view';
            break;
          case 6:
            $options['sort_field'] = 'stick:latest';
            break;
          case 7:
            $options['sort_field'] = 'last_reply';
            break;
          case 8:
            $options['sort_field'] = 'fine:update';
            break;
        }

        //限制输出字段
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'short_title'=>1, 'user_id'=>1, 't_color'=>1, 'top'=>1,
          'fine'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'asset_count'=>1,
          'last_user'=>1, 'last_reply_time'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
          'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'deleted'=>1,'published'=>1, 'tags'=>1,
          'description'=>1, 'attrbute'=>1,
        );
        $options['some_fields'] = $some_fields;
        
        $service = Sher_Core_Service_Topic::instance();
        $resultlist = $service->get_topic_list($query,$options);
        $next_page = 'no';
        if(isset($resultlist['next_page'])){
            if((int)$resultlist['next_page'] > $page){
                $next_page = (int)$resultlist['next_page'];
            }
        }
        
        $max = count($resultlist['rows']);
        for($i=0;$i<$max;$i++){
            $symbol = isset($resultlist['rows'][$i]['user']['symbol']) ? $resultlist['rows'][$i]['user']['symbol'] : 0;
            if(!empty($symbol)){
              $s_key = sprintf("symbol_%d", $symbol);
              $resultlist['rows'][$i]['user'][$s_key] = true;
            }
            if($resultlist['rows'][$i]['asset_count'] > 0){
                $resultlist['rows'][$i]['has_asset'] = true;
                $asset = Sher_Core_Service_Asset::instance();
                $q = array(
                    'parent_id'  => $resultlist['rows'][$i]['_id'],
                    'asset_type' => 55,
                );
                $op = array(
                    'page' => 1,
                    'size' => !empty($resultlist['rows'][$i]['cover'])?4:5,
                    'sort_field' => 'positive',
                );
                $asset_result = $asset->get_asset_list($q, $op);
                $resultlist['rows'][$i]['asset_list'] = $asset_result['rows'];
                
                //print_r($resultlist['rows'][$i]['asset_list']);
            }else{
                $resultlist['rows'][$i]['has_asset'] = false;
            }

            // 过滤用户表
            if(isset($resultlist['rows'][$i]['user'])){
              $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user']);
            }
            if(isset($resultlist['rows'][$i]['last_user'])){
              $resultlist['rows'][$i]['last_user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['last_user']);
            }
        } //end for

        $data = array();
        $data['nex_page'] = $next_page;

        $data['type'] = $type;
        $data['page'] = $page;
        $data['sort'] = $sort;
        $data['size'] = $size;
        $data['results'] = $resultlist;
        
        return $this->ajax_json('', false, '', $data);
    }
	
}
