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
	);
	
	protected $exclude_method_list = array('execute','dream', 'topic', 'allist', 'get_list', 'show');
	
	/**
	 * 社区入口
	 */
	public function execute(){
		return $this->topic();
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
	 * 社区首页
	 */
	public function topic(){
		$prefix_url = Doggy_Config::$vars['app.url.wap.social'].'/c';
		$this->stash['category_prefix_url'] = $prefix_url;
		return $this->to_html_page('wap/topic.html');
	}
	
	/**
	 * 全部话题列表
	 */
	public function get_list(){
		$this->set_target_css_state('getlist');
		$category_id = $this->stash['category_id'];
		
		// 获取某类别列表
		$category = new Sher_Core_Model_Category();
		$child = $category->load((int)$category_id);
		if(empty($child)){
			return $this->show_message_page('请选择某个分类');
		}
		$this->stash['child'] = $child;
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.social', 'c'.$category_id).$page;
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('wap/topic_list.html');
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
		
		return $this->to_html_page('wap/show.html');
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		if (empty($this->stash['cid'])){
			return $this->show_message_page('抱歉，请先选择一个分类！', true);
		}
		$cid = $this->stash['cid'];
		// 是否为一级分类
		$is_top = true;
		$pid = 0;
		$current_category = array();
		$parent_category = array();

		$category = new Sher_Core_Model_Category();
		// 获取当前分类信息
		$current_category = $category->load((int)$cid);
		// 存在父级分类，标识是二级分类
		if (!empty($current_category['pid'])){
			$is_top = false;
			// 获取父级分类
			$parent_category = $category->extend_load((int)$current_category['pid']);
		}

		$this->stash['is_top'] = $is_top;
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		// 判断来源
		if($cid == Doggy_Config::$vars['app.topic.dream_category_id'] || (isset($this->stash['ref']) && $this->stash['ref'] == 'dream')){
			$page_title = '提交创意';
			$this->stash['hide'] = 'hide';
		}else{
			$page_title = '发表话题';
		}
		
		$this->stash['page_title'] = $page_title;
		
		return $this->to_html_page('wap/submit.html');
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
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
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
	
}
?>