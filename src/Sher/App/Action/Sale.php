<?php
/**
 * 预售频道
 * @author purpen
 */
class Sher_App_Action_Sale extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'topic_id'=>'',
		'page'=>1,
		'category_id'=>0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array('execute', 'get_list', 'view');
	
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
	 * 预售列表
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$page = (int)$this->stash['page'];
        
		$pager_url = Sher_Core_Helper_Url::sale_list_url($category_id);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('page/sale/list.html');
	}
	
	/**
	 * 查看详情
	 */
	public function view() {
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.shop'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load($id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非预售状态的产品，跳转至对应的链接
		if(!$product['presale_finished']){
			if($product['stage'] != Sher_Core_Model_Product::STAGE_PRESALE){
				return $this->to_redirect($product['view_url']);
			}
		}
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
        
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $product['designer_id']);
		$this->stash['is_ship'] = $is_ship;
        // 私信用户
        $this->stash['user'] = $product['designer'];
		
		
		return $this->to_html_page('page/sale/show.html');
	}
		
}
?>
