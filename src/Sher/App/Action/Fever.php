<?php
/**
 * 社区-创意投票
 * @author purpen
 */
class Sher_App_Action_Fever extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page'  => 1,
		'type' => 1,
		'category_id' => 0,
		'sort' => 0,
		'step' => 0,
		'page_title_suffix' => '智能硬件创意投票-太火鸟智能硬件孵化平台',
		'page_keywords_suffix' => '太火鸟,智能硬件孵化,智能硬件创意,创意投票,设计创意,硬件创意,创意评论,运动健康,数码电子,智能家居,娱乐生活,户外休闲',
		'page_description_suffix' => '太火鸟智能硬件创意投票专区，提交属于你的智能硬件创意，为你喜欢的智能硬件创意投票，为别人的智能创意发表评论。',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	protected $exclude_method_list = array('execute', 'get_list', 'view', 'ajax_fetch_support');
	
	public function _init() {
		$this->set_target_css_state('page_incubator');
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
	 * 投票列表(正在投票, 即将结束)
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
		$sort = (int)$this->stash['sort'];
		$page = (int)$this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::vote_advance_list_url($category_id,$type,$sort,'#p#');
		$this->stash['pager_url'] = $pager_url;
		$this->gen_advanced_links($category_id, $type, $sort, $page);
		
        // 获取计数
        $dig = new Sher_Core_Model_DigList();
        $counter = $dig->load(Sher_Core_Util_Constant::FEVER_COUNTER);
        $this->stash['counter'] = $counter;
		
		return $this->to_html_page('page/fever/list.html');
	}
	
	/**
	 * 生成高级检索链接
	 */
	protected function gen_advanced_links($category_id=0, $type=1, $sort='latest', $page=1){
		$links = array();
		
		// 类别
		$links['voting_url'] = Sher_Core_Helper_Url::vote_advance_list_url($category_id, 1, $sort, $page);
		$links['finishing_url']  = Sher_Core_Helper_Url::vote_advance_list_url($category_id, 2, $sort, $page);
		switch($type){
			case 1:
				$this->set_target_css_state('type_voting');
				break;
			case 2:
				$this->set_target_css_state('type_finishing');
				break;
			default:
				break;
		}
		
		// 排序
		// 默认最新时间
		$links['sort_latest_url'] = Sher_Core_Helper_Url::vote_advance_list_url($category_id, $type, 0, $page);
		$links['sort_votest_url'] = Sher_Core_Helper_Url::vote_advance_list_url($category_id, $type,  1, $page);
		$links['sort_love_url'] = Sher_Core_Helper_Url::vote_advance_list_url($category_id, $type, 2, $page);
		$links['sort_comment_url'] = Sher_Core_Helper_Url::vote_advance_list_url($category_id, $type, 3, $page);
		
		switch($sort){
			case 0:
				$this->set_target_css_state('sort_latest');
				break;
			case 1:
				$this->set_target_css_state('sort_votest');
				break;
			case 2:
				$this->set_target_css_state('sort_love');
				break;
			case 3:
				$this->set_target_css_state('sort_comment');
				break;
			default:
				break;
		}
		
		$this->stash['links'] = $links;
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
		// 是否允许编辑
		$editable = false;
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的创意不存在或已被删除！', $redirect_url);
		}

		//添加网站meta标签
		$this->stash['page_title_suffix'] = sprintf("%s-太火鸟创意投票专区", $product['title']);
		if(!empty($product['tags_s'])){
		  $this->stash['page_keywords_suffix'] = $product['tags_s'];   
		}
		$this->stash['page_description_suffix'] = sprintf("【%s】是由太火鸟社区活跃用户提交的智能硬件创意，欢迎大家对他的智能硬件创意发表宝贵的意见", $product['short_title']);
			
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非投票状态的产品，跳转至对应的链接
		if(!$product['process_voted']){
			if($product['stage'] != Sher_Core_Model_Product::STAGE_VOTE){
				return $this->to_redirect($product['view_url']);
			}
		}
		
		// 未审核的产品，仅允许本人及管理员查看
		if(!$product['approved'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的创意等待审核中！', $redirect_url);
		}
		
		// 当前用户是否有管理权限
		if ($this->visitor->id){
			if ($this->visitor->id == $product['user_id'] || $this->visitor->can_admin){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 检测是否投票过
		$vote_result = array();
		$support = new Sher_Core_Model_Support();
		$vote = $support->first(array(
			'target_id' => $id,
			'user_id' => $this->visitor->id,
		));
		if (!empty($vote)){
			$vote_result = $support->extended_model_row($vote);
		}
		$this->stash['voted'] = $vote_result;
        
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $product['designer_id']);
		$this->stash['is_ship'] = $is_ship;
        
        // 私信用户
        $this->stash['user'] = $product['designer'];
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::vote_view_url($id,'#p#');
		$this->stash['editable'] = $editable;
		
		$this->stash['product'] = $product;

        // 投诉参数
        $this->stash['report_target_id'] = $product['_id'];
        $this->stash['report_target_type'] = 1;
        $this->stash['target_user_id'] = $product['user_id'];
		
		return $this->to_html_page('page/fever/show.html');
	}
	
	/**
	 * 点击喜欢、赞
	 */
	public function ajax_laud(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		
		try{
			$product = new Sher_Core_Model_Product();
			
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => Sher_Core_Model_Favorite::TYPE_PRODUCT,
			);
			
			// 验证是否赞过
			if (!$model->check_loved($this->visitor->id, $id)){
				$ok = $model->add_love($this->visitor->id, $id, $fav_info);
				
				if ($ok) {
					$product->inc_counter('love_count', 1, $id);
				}
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		$this->stash['mode'] = 'create';
		$this->stash['domain']  = 'fever';
		$this->stash['product'] = $product->extend_load($id); 
		
		return $this->to_taconite_page('ajax/laud_ok.html');
	}
	
	/**
	 * 取消喜欢
	 */
	public function ajax_cancel_laud(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		
		try{
			$product = new Sher_Core_Model_Product();
			
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => Sher_Core_Model_Favorite::TYPE_PRODUCT,
			);
			// 验证是否赞过
			if ($model->check_loved($this->visitor->id, $id)){
				Doggy_Log_Helper::debug('Cancel laud id '.$id);
				$ok = $model->cancel_love($this->visitor->id, $id, $fav_info);
				
				if ($ok) {
					$product->dec_counter('love_count', $id);
				}
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		$this->stash['mode'] = 'cancel';
		$this->stash['domain']  = 'fever';
		$this->stash['product'] = $product->extend_load($id);
		return $this->to_taconite_page('ajax/laud_ok.html');
	}
	
	
	/**
	 * 投票赞成
	 */
	public function ajax_favor(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Support();
			// 验证是否投票过
			if ($model->check_voted($this->visitor->id, $id)){
				return $this->ajax_notification('已经投票成功！', true);
			}
			$data = array(
				'user_id'   => $this->visitor->id,
				'target_id' => $id,
			);
			$ok = $model->apply_and_save($data);
			if (!$ok) {
				return $this->ajax_notification('投票失败，请重试！', true);
			}
			
			$this->stash['ticket'] = 'favor';
			// 获取产品信息
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = $product->extend_load($id);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		return $this->to_taconite_page('ajax/vote_ok.html');
	}
	
	/**
	 * 撤销投票
	 */
	public function ajax_cancel_vote(){
		$id = (int)$this->stash['id'];
		$ticket = $this->stash['ticket'];
		if(empty($id) || empty($ticket)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Support();
			// 验证是否投票过
			if (!$model->check_voted($this->visitor->id, $id)){
				return $this->ajax_notification('抱歉，你没有投票过！', true);
			}
			
			$query = array(
				'user_id'   => $this->visitor->id,
				'target_id' => $id,
				'ticket' => (int)$ticket,
			);
			$ok = $model->remove($query);
			if (!$ok) {
				return $this->ajax_notification('投票失败，请重试！', true);
			}
			$model->mock_after_remove($id, (int)$ticket);
			
			$this->stash['ticket'] = 'cancel';
			$this->stash['user_id'] = $this->visitor->id;
			
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = $product->extend_load($id);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		return $this->to_taconite_page('ajax/vote_ok.html');
	}
	
	/**
	 * 投票反对，及反对理由
	 */
	public function ajax_oppose(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		$reason = $this->stash['r'];
		
		try{
			$model = new Sher_Core_Model_Support();
			// 验证是否投票过
			if ($model->check_voted($this->visitor->id, $id)){
				return $this->ajax_notification('你已经投票成功！', true);
			}
			$data = array(
				'user_id'   => $this->visitor->id,
				'target_id' => $id,
				'ticket' => Sher_Core_Model_Support::TICKET_OPPOSE,
				'reason' => $reason,
			);
			$ok = $model->apply_and_save($data);
			if (!$ok) {
				return $this->ajax_notification('投票失败，请重试！', true);
			}
			
			$this->stash['ticket'] = 'oppose';
			$this->stash['reason'] = $model->oppose_reason((int)$reason);
			
			// 获取产品信息
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = $product->extend_load($id);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		return $this->to_taconite_page('ajax/vote_ok.html');
	}
	
	/**
	 * 专家评估评分
	 */
	public function expert_point(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('访问的创意不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Assess();
			$score = array(
				'usability' => $this->stash['usability'],
				# 外观设计
				'design' => $this->stash['design'],
				# 创意性
				'creativity' => $this->stash['creativity'],
				# 功能性
				'content' => $this->stash['content'],
			);
			
			$data = array(
				'user_id'   => $this->visitor->id,
				'target_id' => $id,
				'score' => $score,
			);
			$ok = $model->apply_and_save($data);
			if (!$ok) {
				return $this->ajax_notification('投票失败，请重试！', true);
			}
			
			// 获取产品信息
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = $product->extend_load($id);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_notification('操作失败！', true);
		}
		
		return $this->to_taconite_page('ajax/point_ok.html');
	}
	
	
	/**
	 * 通过审核
	 */
	public function ajax_approved(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('访问的创意不存在！', true);
		}
		if (!$this->visitor->can_admin()){
			return $this->ajax_json('抱歉，你没有相应权限！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
      $ok = $model->mark_as_approved($id);
      if(!$ok['success']){
        return $this->ajax_json($ok['msg'], true);
      }
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_json('操作失败！', true);
		}
		
		return $this->ajax_json('审核成功！');
	}
	
	/**
	 * 取消审核
	 */
	public function ajax_cancel_approved(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('访问的创意不存在！', true);
		}
		if (!$this->visitor->can_admin()){
			return $this->ajax_json('抱歉，你没有相应权限！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$model->mark_cancel_approved($id);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->ajax_json('操作失败！', true);
		}
		
		return $this->ajax_json('审核成功！');
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$row = array();
		
		$step = (int)$this->stash['step'];
		$id = $this->stash['id'];
		
		switch ($step) {
			case 1:
				$step_tab = 'step_one';
				$tpl_name = 'submit_basic.html';
				break;
			case 2:
				$step_tab = 'step_two';
				$this->stash['mode'] = 'edit';
				
				$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
				$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
				$this->stash['editor_pid'] = new MongoId();
		
				$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
				$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;
					
				$tpl_name = 'submit_content.html';
				break;
			case 3:
				$step_tab = 'step_three';
				$this->stash['mode'] = 'edit';
				
				$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
				$this->stash['pid'] = new MongoId();
				
				$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
				$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
				
				$tpl_name = 'submit_upload.html';	
				break;
			default:
				$step_tab = 'step_default';
				$tpl_name = 'submit.html';
				break;
		}
		
		$this->set_target_css_state($step_tab);
		
		$product = new Sher_Core_Model_Product();
		if(!empty($id)){
			$row = $product->load((int)$id);
	        if (!empty($row)) {
	            $row = $product->extended_model_row($row);
	        }
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
		$product = $model->load($id);
		
		if(empty($product)){
			return $this->show_message_page('创意不存在或已被删除！', $redirect_url);
		}
		
		// 限制修改权限
		if (!$this->visitor->can_admin() && $product['user_id'] != $this->visitor->id){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}

    //如果是通过审核状态禁止用户修改
    if($product['user_id']==$this->visitor->id && !empty($product['published'])){
 			return $this->show_message_page('您的创意产品已经进入投票阶段，如要修改，请联系管理员！', $redirect_url); 
    }
		
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		$this->stash['mode'] = 'edit';
		$this->stash['product'] = $product;
		
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
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		
		try{
			$model = new Sher_Core_Model_Product();
			
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				// 上传者默认为设计师，后台管理可以指定
				$data['designer_id'] = (int)$this->visitor->id;
					
				$ok = $model->apply_and_save($data);
				
				$product = $model->get_data();
				$id = $product['_id'];
				
				// 更新用户主题数量
				$this->visitor->inc_counter('product_count', $data['user_id']);
				
			}else{
				$data['_id'] = $id;
				
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
		
		$data['cover_id'] = $this->stash['cover_id'];
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}
		
		$data['video'] = $this->stash['video'];
		
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
	 * 删除主题
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('创意不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$product = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if ($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id){
        //如果是通过审核状态禁止用户修改
        if($product['user_id']==$this->visitor->id && !empty($product['published'])){
          return $this->ajax_notification('您的创意产品已经进入投票阶段，如要修改，请联系管理员！', true); 
        }
				$model->remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('product_count', $product['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.fever'];
		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}

	/**
	 * ajax删除主题
	 */
	public function ajax_del(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('创意不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$product = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if ($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id){
        //如果是通过审核状态禁止用户修改
        if($product['user_id']==$this->visitor->id && !empty($product['published'])){
          return $this->ajax_notification('您的创意产品已经进入投票阶段，如要修改，请联系管理员！', true); 
        }
				$model->remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('product_count', $product['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}

		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/del_ok.html');
	}
	
	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		
		if (!empty($id)){
			$model = new Sher_Core_Model_Product();
			$model->delete_asset($id, $asset_id);
		}else{
			// 仅仅删除附件
			$asset = new Sher_Core_Model_Asset();
			$asset->delete_file($id);
		}
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}

    /**
     * ajax获取支持者
    */
    public function ajax_fetch_support(){
		$this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
		$this->stash['total_page'] = isset($this->stash['total_page'])?(int)$this->stash['total_page']:1;
		return $this->to_taconite_page('ajax/fetch_support.html');
    }

	/**
	 * 相似灵感提交入口
	 */
	public function stuff_submit(){
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
        if(empty($this->stash['fever_id'])){
			return $this->show_message_page('缺少投票ID！', $redirect_url);
        }
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
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('page/fever/stuff_submit.html');
	}


	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF_EDITOR;
	}
	
}
