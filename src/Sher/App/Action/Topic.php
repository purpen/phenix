<?php
/**
 * 社区帖子
 * @author purpen
 */
class Sher_App_Action_Topic extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'cover_id' => 0,
		'category_id' => 0,
		'sort' => 0,
		'type' => 0,
		'time' => 0,
		'page' => 1,
		'ref'  => null,
	);
	
	protected $page_tab = 'page_topic';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_topic');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_TOPIC;
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 社区列表
	 */
	public function get_list(){
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
		
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}
		
		$category_id = $this->stash['category_id'];
		$type = $this->stash['type'];
		$time = $this->stash['time'];
		$sort = $this->stash['sort'];
		$page = $this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::topic_list_url($category_id);
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;
		
		$this->gen_advanced_links($category_id, $type, $time, $sort, $page);
		
		return $this->to_html_page('page/topic/list.html');
	}
	
	/**
	 * 生成高级检索链接
	 */
	protected function gen_advanced_links($category_id=0, $type=1, $time='all', $sort='latest', $page=1){
		$links = array();
		
		// 类别
		$links['stick_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 1, $time, $sort, $page);
		$links['fine_url']  = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 2, $time, $sort, $page);
		switch($type){
			case 1:
				$this->set_target_css_state('type_stick');
				break;
			case 2:
				$this->set_target_css_state('type_fine');
				break;
			default:
				break;
		}
		
		// 时间
		$links['time_all_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 0, $sort, $page);
		$links['time_day_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 1, $sort, $page);
		$links['time_week_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 2, $sort, $page);
		$links['time_mouth_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 3, $sort, $page);
		$links['time_year_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 4, $sort, $page);
		switch($time){
			case 0:
				$this->set_target_css_state('time_all');
				break;
			case 1:
				$this->set_target_css_state('time_day');
				break;
			case 2:
				$this->set_target_css_state('time_week');
				break;	
			case 3:
				$this->set_target_css_state('time_mouth');
				break;
			case 4:
				$this->set_target_css_state('time_year');
				break;
			default:
				break;
		}
		
		// 排序
		// 默认发帖时间
		$links['sort_default_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 0, $page);
		// 最近回复
		$links['sort_updated_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time,  1,$page);
		$links['sort_comment_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 2, $page);
		$links['sort_favorite_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 3, $page);
		$links['sort_laud_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 4, $page);
		
		switch($sort){
			case 0:
				$this->set_target_css_state('sort_default');
				break;
			case 1:
				$this->set_target_css_state('sort_update');
				break;
			case 2:
				$this->set_target_css_state('sort_comment');
				break;
			case 3:
				$this->set_target_css_state('sort_favorite');
				break;
			case 4:
				$this->set_target_css_state('sort_love');
				break;
			default:
				break;
		}
		
		$this->stash['links'] = $links;
	}
	
	/**
	 * 显示主题详情帖
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = & $model->extend_load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->show_message_page('访问的主题不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		
		
		$this->stash['topic'] = &$topic;
		
		return $this->to_html_page('page/topic/show.html');
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$model->mark_as_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode']  = 'create';
		
		return $this->to_taconite_page('page/topic/stick_ok.html');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode']  = 'cancel';
		
		return $this->to_taconite_page('page/topic/stick_ok.html');
	}
	
	
	/**
	 * 置顶
	 */
	public function ajax_top(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_admin()){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
			$ok = $model->mark_as_top((int)$id);
			
			if ($ok) {
				// 添加到推荐列表
				$diglist = new Sher_Core_Model_DigList();
				$diglist->add_dig(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode']  = 'create';
		
		return $this->to_taconite_page('page/topic/top_ok.html');
	}
	
	/**
	 * 取消置顶
	 */
	public function ajax_cancel_top(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_admin()){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
			$ok = $model->mark_cancel_top((int)$id);
			if ($ok) {
				$diglist = new Sher_Core_Model_DigList();
				$diglist->remove_item(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode']  = 'cancel';
		
		return $this->to_taconite_page('page/topic/top_ok.html');
	}
	
	/**
	 * 点赞
	 */
	public function ajax_laud(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$model->increase_counter('love_count', 1, (int)$id);
		
			$topic = $model->load((int)$id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode']  = 'create';
		$this->stash['topic'] = $topic; 
		
		return $this->to_taconite_page('page/topic/laud_ok.html');
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => Sher_Core_Model_Favorite::TYPE_TOPIC,
			);
			$ok = $model->add_favorite($this->visitor->id, $id, $fav_info);
			
			if ($ok) {
				$topic = new Sher_Core_Model_Topic();
				$topic->increase_counter('favorite_count', 1, (int)$id);
				
				$this->stash['topic'] = $topic->load((int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode'] = 'create';
		
		return $this->to_taconite_page('page/topic/favorite_ok.html');
	}
	
	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->remove_favorite($this->visitor->id, $id);
			
			if ($ok) {
				$topic = new Sher_Core_Model_Topic();
				$topic->dec_counter('favorite_count', (int)$id);
				
				$this->stash['topic'] = $topic->load((int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode'] = 'cancel';
		
		return $this->to_taconite_page('page/topic/favorite_ok.html');
	}
	
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$this->stash['mode'] = 'create';
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 编辑修改主题
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('编辑的主题不存在！', true);
		}
		$model = new Sher_Core_Model_Topic();
		$topic = $model->extend_load((int)$this->stash['id']);
		// 仅管理员或本人具有删除权限
		if (!$this->visitor->can_admin() && !($topic['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
		$this->stash['mode'] = 'edit';
		$this->stash['topic'] = $topic;
		
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 保存主题信息
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
		$data['video_url'] = $this->stash['video_url'];
		
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
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
		
		$redirect_url = Sher_Core_Helper_Url::topic_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
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
	 * 删除主题
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$topic = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if ($this->visitor->can_admin() || $topic['user_id'] == $this->visitor->id){
				$model->remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 从置顶列表中删除
				if ($topic['top']){
					$diglist = new Sher_Core_Model_DigList();
					$diglist->remove_item(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
				}
				
				// 更新用户主题数量
				$this->visitor->dec_counter('topic_count', $topic['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.topic'];
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
}
?>