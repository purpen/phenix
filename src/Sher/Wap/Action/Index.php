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
	
	protected $exclude_method_list = array('execute','home','twelve','comeon','games','clients');
	
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
    //直接跳转游戏页面
    if($this->visitor->id){
      $this->stash['user_id'] = $this->visitor->id;
      $this->stash['nickname'] = $this->visitor->nickname;
    }else{
      $this->stash['user_id'] = 0;
      $this->stash['nickname'] = '';
    }
		return $this->to_html_page('../web/playegg/index.html');
		//return $this->to_html_page('wap/games.html');
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

}
?>
