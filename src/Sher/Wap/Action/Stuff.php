<?php
/**
 * 产品灵感
 * @author purpen
 */
class Sher_Wap_Action_Stuff extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
		'step' => 0,
		'pid'  => 0,
		'cid'  => 0,
		'sort' => 0,
	);
	
	protected $exclude_method_list = array('execute','latest', 'featured', 'sticked', 'view');
	
	protected $page_html = 'page/stuff/zlist.html';
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_stuff');
    }
	
	/**
	 * 产品灵感入口
	 */
	public function execute(){
		return $this->latest();
	}
	
	/**
	 * 最新列表
	 */
	public function latest(){
		return $this->zlist('latest');
	}
	
	/**
	 * 精选列表
	 */
	public function featured(){
		$this->stash['featured'] = 1;
		return $this->zlist('featured');
	}
	
	/**
	 * 推荐列表
	 */
	public function sticked(){
		$this->stash['sticked'] = 1;
		return $this->zlist('sticked');
	}
	
	/**
	 * 产品灵感
	 */
	protected function zlist($list_tab='latest'){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		$top_category_id = Doggy_Config::$vars['app.topic.idea_category_id'];
		$is_top = false;
		if(!$cid || ($cid == $top_category_id)){
			$this->stash['all_stuff'] = 'active';
			$cid = $top_category_id;
			$is_top = true;
		}
		$this->stash['is_top'] = $is_top;
		$this->stash['top_category_id'] = $top_category_id;
		$this->stash['cid'] = $cid;
		
		// 分页链接
		$page = 'p#p#';
		$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.wap.stuff', $list_tab, 'c'.$cid).$page;
		
		return $this->display_tab_page($list_tab);
	}
	
	/**
	 * 灵感详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap.stuff'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的产品不存在或被删除！', $redirect_url);
		}
		
		$stuff = $model->extended_model_row($stuff);
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_admin){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$stuff['fid']);
		
		$this->stash['stuff'] = $stuff;
		$this->stash['parent_category'] = $parent_category;
		$this->stash['editable'] = $editable;
		
    	// 评论参数
    	$this->stash['comment_target_id'] = (int)$stuff['_id'];
    	$this->stash['comment_type'] = Sher_Core_Model_Comment::TYPE_STUFF;
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::stuff_comment_url($id, '#p#');
		
		return $this->to_html_page('wap/stuff/show.html');
	}
	
	/**
	 * 提交入口
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.topic.idea_category_id'];
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = Doggy_Config::$vars['app.url.stuff'];
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('wap/stuff/submit.html');
	}
	
	/**
	 * 编辑修改产品灵感
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load((int)$this->stash['id']);
		
        if(empty($stuff)){
            return $this->show_message_page('编辑的产品不存在或被删除！', true);
        }
		// 仅管理员或本人具有删除权限
		if (!$this->visitor->can_admin() && !($stuff['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
        
		$stuff = $model->extended_model_row($stuff);
		
		// 是否为一级分类
		$is_top = false;
		$current_category = array();
		
		$category = new Sher_Core_Model_Category();
		// 获取当前分类信息
		$current_category = $category->load((int)$stuff['category_id']);
		
		// 获取父级分类
		$parent_category = $category->extend_load((int)$stuff['fid']);
		$parent_category['view_url'] = Doggy_Config::$vars['app.url.stuff'];
		$this->stash['parent_category'] = $parent_category;

		$this->stash['is_top'] = $is_top;
		$this->stash['current_category'] = $current_category;
		$this->stash['cid'] = (int)$current_category['pid'];
		
		$this->stash['mode'] = 'edit';
		$this->stash['stuff'] = $stuff;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		
		$this->stash['pid'] = new MongoId();
		
		$this->_editor_params();
		
		return $this->to_html_page('wap/stuff/submit.html');
	}
	
	/**
	 * 保存产品信息
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

    //所属
    if(isset($this->stash['from_to'])){
      $data['from_to'] = (int)$this->stash['from_to'];
    }
    //团队介绍-蛋年
    if(isset($this->stash['team_introduce'])){
      $data['team_introduce'] = $this->stash['team_introduce'];
    }
		
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$stuff = $model->get_data();
				$id = (int)$stuff['_id'];
				
				// 更新用户灵感数量
				$this->visitor->inc_counter('stuff_count', $data['user_id']);
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], (int)$id);
			}
			
			// 保存成功后，更新编辑器图片
			Doggy_Log_Helper::debug("Upload file count[$file_count].");
			if($file_count && !empty($this->stash['file_id'])){
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		

    if(isset($data['from_to']) && $data['from_to']==2){
      $redirect_url = Doggy_Config::$vars['app.url.birdegg'].'/'.$id.'.html';
    }else{
		  $redirect_url = Sher_Core_Helper_Url::wap_stuff_view_url($id); 
    }
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
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
			$model = new Sher_Core_Model_Stuff();
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
			$model = new Sher_Core_Model_Stuff();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 精选
	 */
	public function ajax_featured(){
		if(empty($this->stash['id'])){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_admin()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$id = $this->stash['id'];
			
			$model = new Sher_Core_Model_Stuff();
			$ok = $model->mark_as_featured((int)$id);
			
			if ($ok) {
				// 添加到精选列表
				$diglist = new Sher_Core_Model_DigList();
				$diglist->add_dig(Sher_Core_Util_Constant::FEATURED_STUFF, (int)$id, Sher_Core_Util_Constant::TYPE_STUFF);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消置顶
	 */
	public function ajax_cancel_featured(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_admin()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Stuff();
			$ok = $model->mark_cancel_featured((int)$id);
			if ($ok) {
				$diglist = new Sher_Core_Model_DigList();
				$diglist->remove_item(Sher_Core_Util_Constant::FEATURED_STUFF, (int)$id, Sher_Core_Util_Constant::TYPE_STUFF);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 删除产品灵感
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			$stuff = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if (!$this->visitor->can_admin() && !($stuff['user_id'] == $this->visitor->id)){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model->remove((int)$id);
			
			// 删除关联对象
			$model->mock_after_remove($id);
			
			// 从精选列表中删除
			if ($stuff['featured']){
				$diglist = new Sher_Core_Model_DigList();
				$diglist->remove_item(Sher_Core_Util_Constant::FEATURED_STUFF, (int)$id, Sher_Core_Util_Constant::TYPE_STUFF);
			}
			
			// 更新所属分类
			$category = new Sher_Core_Model_Category();
			
			$category->dec_counter('total_count', $stuff['category_id']);
			$category->dec_counter('total_count', $stuff['fid']);
			
			// 更新用户主题数量
			$this->visitor->dec_counter('stuff_count', $stuff['user_id']);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.stuff'];
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
		$model = new Sher_Core_Model_Stuff();
		$model->delete_asset($id, $asset_id);
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}
	
}
?>
