<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_Wap_Action_Promo extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute', 'coupon', 'dreamk', 'chinadesign', 'momo');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}
	
	/**
	 * 千万红包
	 */
	public function coupon(){
		$total_times = 3;
		
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		$redis = new Sher_Core_Cache_Redis();
		$times = (int)$redis->get($cache_key);
		
		$this->stash['left_times'] = $total_times - $times;
		
		// 检测是否还有红包
		$bonus = new Sher_Core_Model_Bonus();
		$query = array(
			'used' => Sher_Core_Model_Bonus::USED_DEFAULT,
			'status' => Sher_Core_Model_Bonus::STATUS_OK,
		);
		$result = $bonus->first($query);
		if(!empty($result)){
			$has_bonus = true;
		}else{
			$has_bonus = false;
		}
		$this->stash['has_bonus'] = $has_bonus;
		
		return $this->to_html_page('wap/tweleve.html');
	}
	
	/**
	 *造梦者空气净化器
	 */
	public function dreamk(){
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
	
	/**
	 * 获取红包
	 */
	public function got_bonus(){		
		$total_times = 3;
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		
		$redis = new Sher_Core_Cache_Redis();
		$times = $redis->get($cache_key);
		
		// 设置初始化次数
		if(!$times){
			$times = 0;
		}
		if($times >= $total_times){
			return $this->ajax_note('今天3次机会已用完，明天再来吧！', true);
		}
		
		// 获取红包
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop('T9');
		
		if(empty($result)){
			return $this->ajax_note('红包已抢光了,等待下次机会哦！', true);
		}
		
		// 获取为空，重新生产红包
		/*
		while(empty($result)){
			$bonus->create_batch_bonus(100);
			$result = $bonus->pop('T9');
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}*/
		
		// 赠与红包
		$ok = $bonus->give_user($result['code'], $this->visitor->id);
		if($ok){
			$times += 1;
			$left_times = $total_times - $times;
			
			// 设置次数
			$redis->set($cache_key, $times++);
			
			$this->stash['left_times'] = $left_times;
		}
		
		$this->stash['bonus'] = $result;
		
		return $this->to_taconite_page('ajax/bonus_ok.html');
	}

  	/**
   	 * 55杯-支持原创－专题
     */
  	public function chinadesign(){     
    	$this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
    	$timestamp = $this->stash['timestamp'] = time();
    	$wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    	$wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    	if(empty($_SERVER['QUERY_STRING'])){
      		$url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign';  
    	}else{
        	$url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign?'.$_SERVER['QUERY_STRING'];   
    	}

    	$wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    	$this->stash['wxSha1'] = sha1($wxOri);
    	return $this->to_html_page('wap/chinadesign.html');
  	}
	
	/**
	 * 陌陌新年专题
	 */
	public function momo(){
		$product_ids = array(1082995029,1011468351,1060500658,1060600664,1120700122);
		$relate_ids = array(1111556004,1120700195,1120666085,1092169929,1121112153,1120874607);
		
		$this->stash['product_ids'] = $product_ids;
		$this->stash['relate_ids'] = $relate_ids;
		return $this->to_html_page('wap/momo.html');
	}
	
}
?>
