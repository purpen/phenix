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
    'sort' => 6,
    'page_title_suffix' => '太火鸟话题-智能硬件孵化社区',
    'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
    'page_description_suffix' => '太火鸟话题是国内最大的智能硬件社区，包括智创学堂，孵化需求，活动动态，品牌专区，产品评测等几大社区板块以及上千个智能硬件话题，太火鸟话题-创意与创意的碰撞。',
	);
	
	protected $exclude_method_list = array('execute','dream', 'dream2', 'topic', 'allist', 'allist2', 'get_list', 'show', 'ajax_guess_topics', 'ajax_topic_list');
	
	/**
	 * 社区入口
	 */
	public function execute(){
		return $this->get_list();
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
		return $this->to_html_page('wap/topic.html');
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
    $this->stash['page_title_suffix'] = sprintf("%s-太火鸟智能硬件社区", $topic['title']);
    if(!empty($topic['tags'])){
      $this->stash['page_keywords_suffix'] = sprintf("智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,%s", $topic['tags'][0]);   
    }
    $this->stash['page_description_suffix'] = sprintf("【太火鸟话题】 %s", mb_substr($topic['strip_description'], 0, 140));
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->increase_counter('view_count', $inc_ran, $id);
		
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
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		
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
		$id = (int)$this->stash['_id'];
		
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
        $result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
      }

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
	
}
