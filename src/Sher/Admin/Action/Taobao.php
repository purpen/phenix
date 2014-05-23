<?php
/**
 * 后台淘宝店铺同步管理
 * @author purpen
 */
class Sher_Admin_Action_Taobao extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
		'stage' => 0,
	);
	
	public function execute(){
		$this->set_target_css_state('page_taobao');
		
		return $this->get_list();
	}
	
	/**
     * 用户列表
     * @return string
     */
    public function get_list() {
		$redis = new Sher_Core_Cache_Redis();
		$this->stash['top_session'] = $redis->get('taobao_session');
		
        return $this->to_html_page('admin/taobao/index.html');
    }
	
	/**
	 * 淘宝应用的回调地址
	 * http://localhost/?top_appkey={appkey} &top_parameters=xxx&top_session=xxx&top_sign=xxx
	 * 回调url上的top_session参数即为SessionKey
	 */
	public function call_notify(){
		$top_session = $this->stash['top_session'];
		
		if ($top_session){
			$ttl = 3600;
			$redis = new Sher_Core_Cache_Redis();
			$redis->set('taobao_session', $top_session, $ttl);
			
			$this->stash['msg'] = '已设置Session Key!';
		}else{
			$this->stash['msg'] = '未获取Session Key, 请重试!';
		}
		
		return $this->to_html_page('admin/taobao/call_notify.html');
	}
	
	/**
	 * 获取session key
	 */
	public function get_session_key(){
		$appkey = Doggy_Config::$vars['app.taobao.key'];
		
		$next_url = 'http://container.open.taobao.com/container?appkey='.$appkey;
		
		return $this->to_redirect($next_url);
	}
	
	/**
	 * 查看店铺信息
	 */
	public function store_info(){
		$appkey = Doggy_Config::$vars['app.taobao.key'];
		$secret = Doggy_Config::$vars['app.taobao.secret'];
		$config = array(
		    'appkey' => $appkey,
		    'secretKey' => $secret,
			'format' => 'json'
		);
		$topClient = new \TaobaoTopClient\TopClient($config);
		
		$shopGetRequest = $topClient->getRequest('ShopGetRequest');
		$shopGetRequest->setNick('视觉中国锐店');
		$shopGetRequest->setFields('sid,cid,nick,title,desc,bulletin,created,shop_score,all_count ');
		
		$sessionKey = 'frbird';
		$shopData = $topClient->execute($shopGetRequest, $sessionKey);
		
		print_r($shopData);
	}
	
	/**
	 * 同步淘宝店铺商品
	 */
	public function spider_product() {
		$appkey = Doggy_Config::$vars['app.taobao.key'];
		$secret = Doggy_Config::$vars['app.taobao.secret'];
		$config = array(
		    'appkey' => $appkey,
		    'secretKey' => $secret,
			'format' => 'json'
		);
		try{
			$redis = new Sher_Core_Cache_Redis();
			$session_key = $redis->get('taobao_session');
			if (!$session_key){
				$next_url = 'http://container.open.taobao.com/container?appkey='.$appkey;
				return $this->to_redirect($next_url);
			}
			
			$topClient = new \TaobaoTopClient\TopClient($config);
			$request = $topClient->getRequest('ItemsOnsaleGetRequest');
			$page = 1;
			$page_size = 30;
			$counter = 0;
			
			while (true){
				$request->setFields('approve_status,num_iid,title,nick,type,cid,pic_url,num,props,valid_thru,list_time,price,has_discount,has_invoice,has_warranty,has_showcase,modified,delist_time,postage_id,seller_cids,outer');
				$request->setPageNo($page);
				$request->setPageSize($page_size);
				
				$result = $topClient->execute($request, $session_key);
				
				// 出错或为空时跳出循环
				if(empty($result) || !empty($result['msg'])){
					$this->stash['error_message'] = $result['msg'];
					Doggy_Log_Helper::debug("Sprider taobao, get result:".$result['msg']);
					break;
				}
				
				$total_results = $result['total_results'];
				
				$items = $result['items']['item'];
				$counter += count($items);
				
				$ok = $this->update_product($items);
				
				if ($ok && $counter < $total_results){
					$page += 1;
				} else {
					break;
				}
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
		}
		
		return $this->to_html_page('admin/taobao/spider_product.html');
	}
	
	/**
	 * 更新商品
	 */
	protected function update_product($products=array()) {
		if (empty($products)){
			return false;
		}
		
		for($i=0;$i<count($products);$i++){
			$data = array();
			
			$taobao_iid = $products[$i]['num_iid'];
			
			$data['user_id'] = (int)Doggy_Config::$vars['app.system.user_id'];
			$data['taobao_iid'] = $taobao_iid;
			$data['title'] = $products[$i]['title'];
			$data['sale_price'] = (float)$products[$i]['price'];
			
			$data['stage'] = Sher_Core_Model_Product::STAGE_SHOP;
			$data['approved'] = 1;
			$data['published'] = 0;
			$data['state'] = 0;
			
			$model = new Sher_Core_Model_Product();
			
			$row = $model->first(array('taobao_iid'=>$taobao_iid));
			
			if (empty($row)){
				// 新建产品
				$ok = $model->apply_and_save($data);
				
				$new_data = $model->get_data();
				
				// 抓取主图
				$pic_url = $products[$i]['pic_url'];
				
				Doggy_Log_Helper::warn("Start add fetcher taobao image url [".$pic_url."] -> queue!!");
				
				Sher_Core_Jobs_Queue::fetcher_image($pic_url, array('target_id'=>$new_data['_id']));
				
			}else{
				// 更新产品
				$data['_id'] = $row['_id'];
				$ok = $model->apply_and_update($data);
			}
		}
		
		return true;
	}
	
	
	
	
}
?>