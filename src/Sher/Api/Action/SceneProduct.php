<?php
/**
 * 情景导购产品
 * @author tianshuai
 */
class Sher_Api_Action_SceneProduct extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'outside_search', 'outside_view', 'item_url_convert');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 商品列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['find']) ? (int)$this->stash['find'] : 0;
		$attrbute = isset($this->stash['attrbute']) ? (int)$this->stash['attrbute'] : 0;
		$published = isset($this->stash['published']) ? (int)$this->stash['published'] : 1;
		$state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;

		$some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'oid'=>1, 'sale_price'=>1, 'market_price'=>1,
			'kind'=>1, 'cover_id'=>1, 'category_id'=>1, 'fid'=>1, 'summary'=>1, 'link'=>1, 
			'stick'=>1, 'summary'=>1, 'fine'=>1, 'brand_id'=>1,
			'view_count'=>1, 'favorite_count'=>1, 'love_count'=>1, 'comment_count'=>1,'buy_count'=>1, 'deleted'=>1,
      'published'=>1, 'attrbute'=>1, 'state'=>1, 'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1, 'created_at'=>1,
		);
		
		$query   = array();
		$options = array();
		
    // 查询条件
    if($category_id){
      $query['category_id'] = (int)$category_id;
    }

    if($attrbute){
      $query['attrbute'] = (int)$attrbute;         
    }

    if ($published) {
      if((int)$published==-1){
        $query['published'] = 0;
      }else{
        $query['published'] = 1;         
      }
    }

    if ($stick) {
      if((int)$stick==-1){
        $query['stick'] = 0;
      }else{
        $query['stick'] = 1;         
      }
    }

    if ($fine) {
      if((int)$fine==-1){
        $query['fine'] = 0;
      }else{
        $query['fine'] = 1;         
      }
    }

    if ($state) {
      if((int)$state==-1){
        $query['state'] = 0;
      }else{
        $query['state'] = 1;         
      }
    }

    if($user_id){
      $query['user_id'] = (int)$user_id;         
    }
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:updated';
				break;
			case 2:
				$options['sort_field'] = 'fine:updated';
				break;
			case 3:
				$options['sort_field'] = 'updated';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
    $service = Sher_Core_Service_SceneProduct::instance();
    $result = $service->get_scene_product_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['apc']['view_url'];
			// 用户信息
      if(isset($result['rows'][$i]['user'])){
        $data[$i]['username'] = $result['rows'][$i]['user']['nickname'];
        $data[$i]['small_avatar_url'] = $result['rows'][$i]['user']['small_avatar_url'];     
      }

      // 保留2位小数
      $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);
      // 保留2位小数
      $data[$i]['market_price'] = sprintf('%.2f', $result['rows'][$i]['market_price']);
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 商品详情
	 */
	public function view(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}

    $user_id = $this->current_user_id;
		
		$model = new Sher_Core_Model_SceneProduct();
		$scene_product = $model->load((int)$id);

    if(empty($scene_product)) {
			return $this->api_json('访问的产品不存在！', 3000);
    }
    $scene_product = $model->extended_model_row($scene_product);

		if($scene_product['deleted']){
			return $this->api_json('访问的产品不存在或已被删除！', 3001);
		}

		$some_fields = array(
      '_id', 'title', 'short_title', 'oid', 'sale_price', 'market_price','brand_id',
			'kind', 'cover_id', 'category_id', 'fid', 'summary', 'link', 'description',
			'stick', 'summary', 'fine', 'banner_asset_ids', 'png_asset_ids', 'asset_ids',
			'view_count', 'favorite_count', 'love_count', 'comment_count','buy_count', 'deleted',
      'published', 'attrbute', 'state', 'tags', 'tags_s', 'created_on', 'updated_on', 'created_at',
		);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		// 重建数据结果
		$data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($scene_product[$key]) ? $scene_product[$key] : null;
    }

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $data['is_favorite'] = $fav->check_favorite($this->current_user_id, $scene_product['_id'], 10) ? 1 : 0;
    $data['is_love'] = $fav->check_loved($this->current_user_id, $scene_product['_id'], 10) ? 1 : 0;

    //返回图片数据
    $assets = array();
    $asset_query = array('parent_id'=>$scene_product['_id'], 'asset_type'=>120);
    $asset_options['page'] = 1;
    $asset_options['size'] = 10;
    $asset_service = Sher_Core_Service_Asset::instance();
    $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

    if(!empty($asset_result['rows'])){
      foreach($asset_result['rows'] as $key=>$value){
        array_push($assets, $value['thumbnails']['aub']['view_url']);
      }
    }
    $data['banner_asset'] = $assets;
    $data['cover_url'] = $scene_product['cover']['thumbnails']['apc']['view_url'];
      

		return $this->api_json('请求成功', 0, $data);
	}




  /**
   * 站外搜索，包括淘宝、天猫
   * @author tianshuai
   * @param q:搜索内容；evt: 1.淘宝天猫、2.京东; sort: 排序;
   */
  public function outside_search(){
    $result = array();
 		$q = isset($this->stash['q']) ? $this->stash['q'] : null;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 1;
    // 所在城市
    $city = isset($options['city']) ? $options['city'] : null;
    $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;

    $options = array(
      'page' => $page,
      'size' => $size,
      'platform' => $platform,
      'city' => $city,
    );

    if(empty($q)){
      return $this->api_json('缺少请求参数!', 3001);
    }

    if($evt==1){
      $result = Sher_Core_Util_TopSdk::search($q, $options);
    }elseif($evt==2){
      $result = Sher_Core_Util_JdSdk::search($q, $options);  
    }else{
      return $this->api_json('搜索类型不正确!', 3002);    
    }

    if($result['success']){
      return $this->api_json('success', 0, $result['data']);   
    }else{
      return $this->api_json($result['msg'], 3005);
    }


  }

  /*
   * 站外商品查询
   */
  public function outside_view(){
    $result = $options = array();
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    $ids = isset($this->stash['ids']) ? $this->stash['ids'] : null;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 2;
    if(empty($ids)){
      return $this->api_json('缺少请求参数!', 3001);    
    }

    $options['platform'] = $platform;

    if($evt==1){
      $result = Sher_Core_Util_TopSdk::search_by_item($ids, $options);
    }elseif($evt==2){
      $result = Sher_Core_Util_JdSdk::search($ids, $options);  
    }else{
      return $this->api_json('搜索类型不正确!', 3002);
    }

    if($result['success']){
      return $this->api_json('success', 0, $result['data']);     
    }else{
      return $this->api_json($result['msg'], 3005);
    }

  
  }


  /**
   * 淘宝商品链接转换
   */
  public function item_url_convert(){
    $result = $options = array();
    $ids = '525850484428,526606940091';

    $result = Sher_Core_Util_TopSdk::item_url_convert($ids, $options); 
  
  }

	

}

