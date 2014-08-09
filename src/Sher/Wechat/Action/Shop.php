<?php
/**
 * 微信商店
 * @author purpen
 */
class Sher_Wechat_Action_Shop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'sku'=>'',
		'type' => 0,
		'category_id' => 0,
		'sort' => 0,
		'topic_id'=>'',
		'page'=>1,
		'rrid' => 0,
		'n'=>1, // 数量
		's' => 1, // 型号
		'page' => 1,
		'payaway' => '', // 支付机构
		
		'openid' => '',
		'code' => '',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/wechat/index.html';
	
	// 配置微信参数
	public $options = array();
	
	protected $exclude_method_list = array('execute','featured','newest','get_list','view', 'z');
	
	public function _init() {
		$this->options = array(
			'token'=>Doggy_Config::$vars['app.wechat.ser_token'],
			'appid'=>Doggy_Config::$vars['app.wechat.ser_app_id'],
			'appsecret'=>Doggy_Config::$vars['app.wechat.ser_app_secret'],
			'partnerid'=>Doggy_Config::$vars['app.wechat.ser_partner_id'],
			'partnerkey'=>Doggy_Config::$vars['app.wechat.ser_partner_key'],
			'paysignkey'=>Doggy_Config::$vars['app.wechat.ser_paysignkey'] //商户签名密钥Key
		);
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->featured();
	}
	
	/**
	 * 精选商品列表
	 */
	public function featured(){
		$this->set_target_css_state('list_featured');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.wechat'].'/shop/featured?page=#p#';
		$this->stash['sort'] = 'hot';
		
		return $this->get_list();
	}
	
	/**
	 * 最新商品列表
	 */
	public function newest() {
		$this->set_target_css_state('list_newest');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.wechat'].'/shop/newest?page=#p#';
		$this->stash['sort'] = 'latest';
		
		return $this->get_list();
	}
	
	/**
	 * 商品列表
	 */
	public function get_list() {
		$page = (int)$this->stash['page'];
		
		return $this->to_html_page('page/wechat/list.html');
	}
	
	/**
	 * 智造革命专题
	 */
	public function z(){
		return $this->to_html_page('page/wechat/z.html');
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
		$product = $model->extend_load((int)$id);
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非预售状态的产品，跳转至对应的链接
		if($product['stage'] != Sher_Core_Model_Product::STAGE_SHOP){
			return $this->to_redirect($product['view_url']);
		}
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		
		return $this->to_html_page('page/wechat/view.html');
	}
	
	
	
}
?>