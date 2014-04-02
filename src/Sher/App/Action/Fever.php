<?php
/**
 * 社区-创意投票
 * @author purpen
 */
class Sher_App_Action_Fever extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
		'step' => 0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_fever');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 社区-创意投票
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 投票列表
	 */
	public function get_list() {
		return $this->to_html_page('page/fever/list.html');
	}
	
	/**
	 * 查看详情
	 */
	public function view() {
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('访问的创意不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = & $model->extend_load($id);
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的创意不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::vote_view_url($id,'#p#');
		
		$this->stash['product'] = &$product;
		
		
		return $this->to_html_page('page/fever/show.html');
	}
	
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$row = array();
		$step = (int)$this->stash['step'];
		switch ($step) {
			case 1:
				$step_tab = 'step_one';
				$tpl_name = 'submit_basic.html';
				break;
			case 2:
				$step_tab = 'step_two';
				$this->stash['mode'] = 'edit';
				$tpl_name = 'submit_content.html';
				break;
			case 3:
				$step_tab = 'step_three';
				$this->stash['mode'] = 'edit';
				$tpl_name = 'submit_upload.html';
				break;
			default:
				$step_tab = 'step_default';
				$tpl_name = 'submit.html';
		}
		$this->set_target_css_state($step_tab);
		
		$product = new Sher_Core_Model_Product();
		if(isset($this->stash['id']) && !empty($this->stash['id'])){
			$row = $product->extend_load((int)$this->stash['id']);
		}
		$this->stash['product'] = $row;
		
		return $this->to_html_page('page/fever/'.$tpl_name);
	}
	
	/**
	 * 编辑创意
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('创意不存在！', $redirect_url);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = & $model->extend_load($id);
		
		// 限制修改权限
		if (!$this->visitor->can_admin() || $product['user_id'] != $this->visitor->id){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
		
		$this->stash['mode'] = 'edit';
		$this->stash['product'] = &$product;
		
		return $this->to_html_page('page/fever/submit_basic.html');
	}
	
	
	/**
	 * 保存产品创意信息
	 */
	public function save(){
		$step = (int)$this->stash['step'];
		Doggy_Log_Helper::debug("Start save step[$step].");
		switch ($step){
			case 1:
				return $this->save_basic();
			case 2:
				return $this->save_content();
			case 3:
				Doggy_Log_Helper::debug("Start save step[$step] upload.");
				return $this->save_upload();
			default:
				break;
		}
	}
	
	/**
	 * 保存创意基本信息
	 */
	protected function save_basic(){
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('创意名称不能为空！', true);
		}
		
		$id = (int)$this->stash['_id'];
		
		// 分步骤保存信息
		$data = array();
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		
		try{
			$model = new Sher_Core_Model_Product();
			
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$product = $model->get_data();
				$id = $product['_id'];
				
				// 更新用户主题数量
				$this->visitor->inc_counter('product_count', $data['user_id']);
				
			}else{
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$next_url = Doggy_Config::get('app.url.fever').'/submit?step=2&id='.$id;
		
		return $this->ajax_json('保存成功.', false, $next_url);
	}
	
	/**
	 * 保存创意详情内容
	 */
	protected function save_content(){
		$id = (int)$this->stash['_id'];
		// 验证数据
		if(empty($id)){
			return $this->ajax_json('创意主题不存在！', true);
		}
		
		$data = array();
		$data['_id'] = $id;
		$data['content'] = $this->stash['content'];
		
		try{
			$model = new Sher_Core_Model_Product();
			$ok = $model->apply_and_update($data);
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$next_url = Doggy_Config::get('app.url.fever').'/submit?step=3&id='.$id;
		
		return $this->ajax_json('保存成功.', false, $next_url);
	}
	
	/**
	 * 保存附件图片及视频
	 */
	protected function save_upload(){
		$id = (int)$this->stash['_id'];
		// 验证数据
		if(empty($id)){
			return $this->ajax_json('创意主题不存在！', true);
		}
		
		$data = array();
		$data['_id'] = $id;
		$data['meta'] = $this->stash['meta'];
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}
		
		try{
			Doggy_Log_Helper::debug("Start save  product[$id] 's upload files.");
			
			$model = new Sher_Core_Model_Product();
			$ok = $model->apply_and_update($data);
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$next_url = Sher_Core_Helper_Url::vote_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $next_url);
	}
	
	/**
	 * 设置创意的封面图
	 */
	public function update_cover() {
		$id = (int)$this->stash['id'];
		$cover_id = $this->stash['cover_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->extend_load($id);
		
		// 限制设置权限
		if (!$this->visitor->can_admin() || $product['user_id'] != $this->visitor->id){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
		
		$model->mark_set_cover($id, $cover_id);
		
		return $this->ajax_notification('设置成功！', false);
	}
	
}
?>