<?php
/**
 * Wap首页
 * @author purpen
 */
class Sher_Wap_Action_Index extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','home','twelve','comeon');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->home();
	}
	
	/**
	 * 首页
	 */
	public function home(){
		return $this->to_html_page('wap/index.html');
	}
	
	/**
	 * 404 page
	 */
	public function not_found(){
		return $this->to_html_page('page/404.html');
	}
	
	/**
	 * 双12活动
	 */
	public function twelve(){
		return $this->to_html_page('wap/publish.html');
	}
	
	/**
	 * 游戏
	 */
	public function games(){
		return $this->to_html_page('wap/games.html');
	}
	
	/**
	 * 客户端
	 */
	public function clients(){
		return $this->to_html_page('wap/clients.html');
	}
	
	/**
	 * 就现在能量线
	 */
	public function comeon(){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $product_id);
		
		$this->stash['product'] = $product;
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		// 验证是否预约
		if($this->visitor->id){
			$cache_key = sprintf('appoint_%d_%d', $product_id, $this->visitor->id);
			$redis = new Sher_Core_Cache_Redis();
			$appointed = $redis->get($cache_key);
		}else{
			$appointed = false;
		}
		$this->stash['appointed'] = $appointed;
		
		return $this->to_html_page('wap/noodles.html');
	}

	/**
	 * 坐享新风 免费领口罩
	 */
	public function mask(){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $product_id);
		
		$this->stash['product'] = $product;

    $this->stash['is_time'] = false;
    if($product['can_saled']){
      if($product['snatched_time']<time()){
        $this->stash['is_time'] = true;
      }
    }
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
    $this->stash['has_address'] = false;
		
		// 验证是否预约
		if($this->visitor->id){
			$cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
			$redis = new Sher_Core_Cache_Redis();
      $appointed = $redis->get($cache_key);
      //是否有默认地址
      $addbook_model = new Sher_Core_Model_AddBooks();
      $addbook = $addbook_model->first(array('user_id'=>$this->visitor->id));
      if(!empty($addbook)){
        $this->stash['has_address'] = true;
      }
		}else{
			$appointed = false;
		}
		$this->stash['appointed'] = $appointed;
		
		return $this->to_html_page('wap/dreamk.html');
	}


}
?>
