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
	
	protected $exclude_method_list = array('execute', 'index', 'get_list', 'view');
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_topic');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_TOPIC;
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 社区首页
	 */
	public function index(){
		return $this->to_html_page('page/topic/index.html');
	}
	
	/**
	 * 社区列表
	 */
	public function get_list(){
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
		$current_category = array();
		$parent_category = array();
		
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}
		
		// 获取列表
		$category_id = $this->stash['category_id'];
		$type = $this->stash['type'];
		$time = $this->stash['time'];
		$sort = $this->stash['sort'];
		$page = $this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::topic_list_url($category_id, $type, $time, $sort).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;
		
		$this->gen_advanced_links($category_id, $type, $time, $sort, $page);
		
		// 是否为一级分类
		$is_top = true;
		// 获取当前分类信息
		if ($category_id){
			$category = new Sher_Core_Model_Category();
			$current_category = $category->extend_load((int)$category_id);
			// 存在父级分类，标识是二级分类
			if (!empty($current_category['pid'])){
				$is_top = false;
				// 获取父级分类
				$parent_category = $category->extend_load((int)$current_category['pid']);
			}
		}
		
		$this->stash['cid'] = $this->stash['category_id'];
		$this->stash['is_top'] = $is_top;
		
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = $parent_category;
		
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
	 * 某产品的话题
	 */
	public function subject(){
		$id = (int)$this->stash['id'];
		
		// 获取产品专区话题列表
		$category_id = Doggy_Config::$vars['app.product.topic_category_id'];
		$category = new Sher_Core_Model_Category();
		$this->stash['subject_category'] = $category->extend_load($category_id);
		
		$product = new Sher_Core_Model_Product();
		$this->stash['product'] = & $product->extend_load($id);
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::product_subject_url($id, '#p#');
		
		return $this->to_html_page('page/topic/subject_list.html');
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
		// 是否允许编辑
		$editable = false;
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		$tpl = 'page/topic/show.html';
		
		$model = new Sher_Core_Model_Topic();
		$topic = & $model->extend_load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->show_message_page('访问的主题不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
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
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		// 判定是否产品话题
		if (isset($topic['target_id']) && !empty($topic['target_id'])){
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = & $product->extend_load($topic['target_id']);
			$tpl = 'page/topic/subject_show.html';
		}
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$model->mark_as_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
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
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
			$ok = $model->mark_as_top((int)$id);
			
			if ($ok) {
				// 添加到推荐列表
				$diglist = new Sher_Core_Model_DigList();
				$diglist->add_dig(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消置顶
	 */
	public function ajax_cancel_top(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_admin()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
			$ok = $model->mark_cancel_top((int)$id);
			if ($ok) {
				$diglist = new Sher_Core_Model_DigList();
				$diglist->remove_item(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_TOPIC;
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
		
		$this->editor_params();
		
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
		
		// 是否为一级分类
		$is_top = false;
		$current_category = array();
		$parent_category = array();
		
		$category = new Sher_Core_Model_Category();
		// 获取当前分类信息
		$current_category = $category->load((int)$topic['category_id']);
		// 获取父级分类
		$parent_category = $category->load((int)$topic['fid']);

		$this->stash['is_top'] = $is_top;
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['cid'] = $topic['category_id'];
		
		
		$this->stash['mode'] = 'edit';
		$this->stash['topic'] = $topic;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
		
		$this->editor_params();
		
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
		
		// 检测编辑器图片数
		$file_count = isset($this->stash['file_count']) ? (int)$this->stash['file_count'] : 0;
		
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
			
			// 保存成功后，更新编辑器图片
			Doggy_Log_Helper::debug("Upload file count[$file_count].");
			if($file_count && !empty($this->stash['file_id'])){
				$model->update_editor_asset($id, $this->stash['file_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Sher_Core_Helper_Url::topic_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 保存产品话题信息
	 */
	public function ajax_save(){
		// 验证数据
		$target_id = $this->stash['target_id'];
		if(empty($this->stash['title']) || empty($this->stash['description'])){
			return $this->ajax_json('标题和内容不能为空！', true);
		}
		$id = (int)$this->stash['_id'];
		$mode = 'create';
		
		$data = array();
		
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		$data['target_id'] = (int)$this->stash['target_id'];
		
		// 产品话题分类Id
		$data['category_id'] = (int) Doggy_Config::$vars['app.product.topic_category_id'];
		
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
			
			$this->stash['topic'] = &DoggyX_Model_Mapper::load_model($id,'Sher_Core_Model_Topic');
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		return $this->to_taconite_page('ajax/product_topic.html');
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
				
				// 更新所属分类: 主题数、回复数
				$category = new Sher_Core_Model_Category();
				
				$category->dec_counter('total_count', $topic['category_id']);
				$category->dec_counter('total_count', $topic['fid']);
				$category->dec_counter('reply_count', $topic['category_id'], false, $topic['comment_count']);
				$category->dec_counter('reply_count', $topic['fid'], false, $topic['comment_count']);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('topic_count', $topic['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.topic'];
		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	
	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($id) || empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		$model = new Sher_Core_Model_Topic();
		$model->delete_asset($id, $asset_id);
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}
}
?>