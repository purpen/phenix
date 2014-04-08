<?php
/**
 * 预售频道
 * @author purpen
 */
class Sher_App_Action_Sale extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'topic_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	
	public function _init() {
		$this->set_target_css_state('page_sale');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
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
	public function get_list() {
		return $this->to_html_page('page/sale/list.html');
	}
	
	
	/**
	 * 查看详情
	 */
	public function view() {
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->extend_load($id);
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非预售状态的产品，跳转至对应的链接
		if($product['stage'] != Sher_Core_Model_Product::STAGE_PRESALE){
			return $this->to_redirect($product['view_url']);
		}
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		
		
		return $this->to_html_page('page/sale/show.html');
	}
	
	/**
	 * 预售设置
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('产品不存在！', $redirect_url);
		}
		
		// 限制修改权限
		if (!$this->visitor->can_admin()){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = & $model->extend_load($id);
		
		
		$this->stash['product'] = $product;
		
		return $this->to_html_page('page/sale/edit.html');
	}
	
	/**
	 * 保存产品的销售信息
	 */
	public function save(){		
		$id = (int)$this->stash['_id'];
		
		// 分步骤保存信息
		$data = array();
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		
		$data['cost_price'] = $this->stash['cost_price'];
		$data['market_price'] = $this->stash['market_price'];
		$data['hot_price'] = $this->stash['hot_price'];
		
		$data['mode'] = $this->stash['mode'];
		$data['quantity'] = $this->stash['quantity'];
		
		try{
			$model = new Sher_Core_Model_Product();
			
			$ok = $model->apply_and_update($data);
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$view_url = Sher_Core_Helper_Url::sale_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $view_url);
	}
	
	
	
}
?>