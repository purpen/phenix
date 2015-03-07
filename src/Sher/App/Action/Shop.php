<?php
/**
 * 商店
 * @author purpen
 */
class Sher_App_Action_Shop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'sku' => '',
		'type' => 0,
		'category_id' => 0,
		'sort' => 1,
		'topic_id' => '',
		'page' => 1,
		'size' => 3,
		'sword' => '',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/shop/index.html';
	
	protected $exclude_method_list = array('execute','get_list','view','ajax_fetch_comment','check_snatch_expire');
	
	public function _init() {
		$this->set_target_css_state('page_shop');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 商店首页
	 */
	public function index(){
		return $this->to_html_page('page/shop/home.html');
	}
	
	/**
	 * 商店列表
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
		$sort = (int)$this->stash['sort'];
		$page = (int)$this->stash['page'];
		$current_category = array();
		
	    $presale = isset($this->stash['presale'])?(int)$this->stash['presale']:0;
	    $this->stash['all_active'] = false;
	    $this->stash['presale_active'] = false;
	    if(empty($presale)){
			$this->stash['is_shop'] = 1;
			$this->stash['presaled'] = 0;
			if($category_id == 0){
				$this->stash['all_active'] = true;
				$current_category = array('name' => 'all');
			}
			$pager_url = Sher_Core_Helper_Url::shop_list_url($category_id, $type, $sort,'#p#');
			$list_prefix = Doggy_Config::$vars['app.url.shop'];
	    }else{
			$this->stash['is_shop'] = 0;
			$this->stash['presaled'] = 1;
			if($category_id == 0){
				$this->stash['presale_active'] = true;
			}
			$pager_url = Sher_Core_Helper_Url::sale_list_url($category_id, $type, $sort,'#p#');
			$list_prefix = Doggy_Config::$vars['app.url.sale'];
			
			$current_category = array('name'=>'presale');
	    }
		// 排序方式
		switch($sort){
			case 1:
				$sort_text = 'latest';
				break; 
			case 2:
				$sort_text = 'hot';
				break;
			case 3:
				$sort_text = empty($presale) ? 'price' : 'money';
				break;
			case 4:
				$sort_text = empty($presale) ? 'sales' : 'presales';
				break;
			default:
				$sort_text = 'stick:latest';
				break;
		}
		$this->stash['sort_text'] = $sort_text;
		
		$this->stash['pager_url'] = $pager_url;
		$this->stash['list_prefix'] = $list_prefix;
		
		// 获取当前类别
		if($category_id){
			$category = new Sher_Core_Model_Category();
			$current_category = $category->load((int)$category_id);
		}
		$this->stash['current_category'] = $current_category;
		
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
		$this->stash['skus_count'] = count($skus);
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		
		return $this->to_html_page('page/shop/show.html');
	}
	
	/**
	 * 获取推荐产品
	 */
	public function ajax_guess_product(){
		$sword = $this->stash['sword'];
		$size = $this->stash['size'] || 3;
		
		$result = array();
		$options = array(
			'page' => 1,
			'size' => $size,
			'sort_field' => 'latest',
		);
		if(!empty($sword)){
			$result = Sher_Core_Service_Search::instance()->search($sword, 'full', array('type' => 1), $options);
		}
		
		$this->stash['result'] = $result;
		
		return $this->to_taconite_page('ajax/guess_products.html');
	}
	
	/**
	 * ajax获取评论
	 */
	public function ajax_fetch_comment(){
		$this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
		$this->stash['total_page'] = isset($this->stash['total_page'])?(int)$this->stash['total_page']:1;
		return $this->to_taconite_page('ajax/fetch_shop_comment.html');
	}

  	/**
   	 * 产品合作入口
   	 */
  	public function cooperate(){
        $this->set_target_css_state('page_cooperate');
   		return $this->to_html_page('page/shop/cooperate.html');
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
			$model = new Sher_Core_Model_Contact();
			$model->delete_asset($id, $asset_id);
		}else{
			// 仅仅删除附件
			$asset = new Sher_Core_Model_Asset();
			$asset->delete_file($id);
		}
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}

  /**
   * 抢购倒计时确认
   */
  public function check_snatch_expire(){
    $id = $this->stash['product_id'];
		$model = new Sher_Core_Model_Product();
    $product = $model->load((int)$id);
    if(empty($product)){
      return $this->ajax_json('商品未找到!', true);
    }
    if($product['snatched_time'] <= time()){
      return $this->ajax_json('操作成功', false);
    }else{
      return $this->ajax_json('您的系统时间不准确,请刷新页面查看结果!', true);
    }
  }
	
}
?>
