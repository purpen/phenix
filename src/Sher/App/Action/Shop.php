<?php
/**
 * 商店
 * @author purpen
 */
class Sher_App_Action_Shop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'sku'=>'',
		'type' => 0,
		'category_id' => 0,
		'sort' => 0,
		'topic_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array('execute','get_list','view');
	
	public function _init() {
		$this->set_target_css_state('page_shop');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 商店列表
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
		$sort = (int)$this->stash['sort'];
		$page = (int)$this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::shop_list_url($category_id,$type,$sort,'#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('page/shop/index.html');
	}
	
	/**
	 * 查看产品详情
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
		$product = $model->load((int)$id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非销售状态的产品，跳转至对应的链接
		if($product['stage'] != Sher_Core_Model_Product::STAGE_SHOP){
			return $this->to_redirect($product['view_url']);
		}
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 获取skus及inventory
		$inventory = new Sher_Core_Model_Inventory();
		$skus = $inventory->find(array(
			'product_id' => $id,
			'stage' => $product['stage'],
		));
		$this->stash['skus'] = $skus;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		
		return $this->to_html_page('page/shop/show.html');
	}
	
}
?>