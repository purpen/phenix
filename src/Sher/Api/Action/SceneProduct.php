<?php
/**
 * 情景导购产品
 * @author tianshuai
 */
class Sher_Api_Action_SceneProduct extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'outside_search', 'tb_view', 'jd_view', 'jd_item_price', 'item_url_convert', 'sight_click_stat');

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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 请求参数
		$ids = isset($this->stash['ids']) ? $this->stash['ids'] : null;
		$ignore_ids = isset($this->stash['ignore_ids']) ? $this->stash['ignore_ids'] : null;
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$category_tag_ids = isset($this->stash['category_tag_ids']) ? $this->stash['category_tag_ids'] : null;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
    $brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : null;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['fine']) ? (int)$this->stash['fine'] : 0;
		$attrbute = isset($this->stash['attrbute']) ? (int)$this->stash['attrbute'] : 0;
		$published = isset($this->stash['published']) ? (int)$this->stash['published'] : 1;
		$state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;
		$kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
		$ignore_sight_id = isset($this->stash['ignore_sight_id']) ? (int)$this->stash['ignore_sight_id'] : 0;

		$some_fields = array(
			'_id'=>1, 'title'=>1, 'short_title'=>1, 'oid'=>1, 'sale_price'=>1, 'market_price'=>1,
			'kind'=>1, 'cover_id'=>1, 'category_id'=>1, 'fid'=>1, 'summary'=>1, 'link'=>1, 
			'stick'=>1, 'summary'=>1, 'fine'=>1, 'brand_id'=>1, 'cover_url'=>1, 'banner_id'=>1,
			'view_count'=>1, 'favorite_count'=>1, 'love_count'=>1, 'comment_count'=>1,'buy_count'=>1, 'deleted'=>1,
			'published'=>1, 'attrbute'=>1, 'state'=>1, 'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1, 'created_at'=>1,
			'category_tags'=>1,
		);
		
		$query   = array();
		$options = array();

    if($kind){
      $query['kind'] = (int)$kind;
    }

    if($ids){
      $id_arr = explode(',', $ids);
      for($i=0;$i<count($id_arr);$i++){
        $id_arr[$i] = (int)$id_arr[$i];
      }
      if(!empty($id_arr)){
        $query['_id'] = array('$in'=>$id_arr);
        unset($query['kind']);
      }   
    }
		
    // 查询条件
    if($category_id){
      $query['category_id'] = (int)$category_id;
    }

    // 查询条件
    if($category_tag_ids){
      $category_tag_arr = explode(',', $category_tag_ids);
      for($i=0;$i<count($category_tag_arr);$i++){
        $category_tag_arr[$i] = (int)$category_tag_arr[$i];
      }
      $query['category_tags'] = array('$in'=>$category_tag_arr);
    }

    // 过滤的商品
    if($ignore_ids){
      $id_arr = explode(',', $ignore_ids);
      for($i=0;$i<count($id_arr);$i++){
        $id_arr[$i] = (int)$id_arr[$i];
      }
      if(!empty($id_arr)){
        $query['_id'] = array('$ne'=>$id_arr);
      }   
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

    // 品牌查询
    if($brand_id){
      $query['brand_id'] = $brand_id;
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

    $asset_service = Sher_Core_Service_Asset::instance();

    $spl_model = new Sher_Core_Model_SceneProductLink();
    $sight_model = new Sher_Core_Model_SceneSight();
		
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

      //返回Banner图片数据
      $assets = array();
      $asset_query = array('parent_id'=>$data[$i]['_id'], 'asset_type'=>120);
      $asset_options['page'] = 1;
      $asset_options['size'] = 8;
      $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

      $data[$i]['banner_id'] = isset($data[$i]['banner_id']) ? $data[$i]['banner_id'] : null;
      $banner_asset_obj = false;
      if(!empty($asset_result['rows'])){
        foreach($asset_result['rows'] as $key=>$value){
          if($data[$i]['banner_id']==(string)$value['_id']){
            $banner_asset_obj = $value;
          }else{
            array_push($assets, $value['thumbnails']['aub']['view_url']);
          }
        }
        // 如果存在封面图，追加到第一个
        if($banner_asset_obj){
          array_unshift($assets, $banner_asset_obj['thumbnails']['aub']['view_url']);
        }
      }
      $data[$i]['banner_asset'] = $assets;

      // 保留2位小数
      $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);
      // 保留2位小数
      $data[$i]['market_price'] = sprintf('%.2f', $result['rows'][$i]['market_price']);

      $sights = array();
      // 取一张场景图
      if($ignore_sight_id){
        $sight_query['sight_id'] = array('$ne'=>$ignore_sight_id);
      }
      $sight_query['product_id'] = $data[$i]['_id'];

      $sight_options['page'] = 1;
      $sight_options['size'] = 1;
      $sight_options['sort'] = array('created_on'=>-1);
      $sqls = $spl_model->find($sight_query, $sight_options);
      if($sqls){
        for($j=0;$j<count($sqls);$j++){
          $sight_id = $sqls[$j]['sight_id'];
          $sight = $sight_model->extend_load((int)$sight_id);
          if(!empty($sight) && isset($sight['cover'])){
            array_push($sights, array('id'=>$sight['_id'], 'title'=>$sight['title'], 'cover_url'=>$sight['cover']['thumbnails']['asc']['view_url']));
          }
        }
      }
      $data[$i]['sights'] = $sights;

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
			return $this->api_json('缺少请求参数！', 3000);
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
      '_id', 'title', 'short_title', 'oid', 'sale_price', 'market_price','brand_id', 'brand',
			'kind', 'cover_id', 'category_id', 'fid', 'summary', 'link', 'description',
			'stick', 'summary', 'fine', 'banner_asset_ids', 'png_asset_ids', 'asset_ids', 'category_tags',
			'view_count', 'favorite_count', 'love_count', 'comment_count','buy_count', 'deleted',
      'published', 'attrbute', 'state', 'tags', 'tags_s', 'created_on', 'updated_on', 'created_at', 'cover_url',
		);

    $brand_some_fields = array(
      'title', 'des',
    );
		
		// 增加浏览量
    $rand = rand(1, 5);
		$model->inc_counter('view_count', $rand, $id);
		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('app_view_count', 1, $id);

		// 重建数据结果
		$data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($scene_product[$key]) ? $scene_product[$key] : null;
    }

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $data['is_favorite'] = 0;
    if(!empty($user_id)){
        $data['is_favorite'] = $fav->check_favorite($user_id, $scene_product['_id'], 10) ? 1 : 0;
    }
    //$data['is_love'] = $fav->check_loved($user_id, $scene_product['_id'], 10) ? 1 : 0;

    // 过滤品牌
    if(!empty($data['brand'])){
      $brand_data = array();
      $brand_data['cover_url'] = isset($data['brand']['cover']['thumbnails']['huge']['view_url']) ? $data['brand']['cover']['thumbnails']['huge']['view_url'] : null;
      $brand_data['_id'] = (string)$data['brand']['_id'];
      for($j=0;$j<count($brand_some_fields);$j++){
        $brand_key = $brand_some_fields[$j];
        $brand_data[$brand_key] = isset($data['brand'][$brand_key]) ? $data['brand'][$brand_key] : null;
      }
      $data['brand'] = $brand_data;
    }

    $asset_service = Sher_Core_Service_Asset::instance();

    //返回Banner图片数据
    $assets = array();
    $asset_query = array('parent_id'=>$scene_product['_id'], 'asset_type'=>120);
    $asset_options['page'] = 1;
    $asset_options['size'] = 10;
    $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

    if(!empty($asset_result['rows'])){
      foreach($asset_result['rows'] as $key=>$value){
        array_push($assets, $value['thumbnails']['aub']['view_url']);
      }
    }
    $data['banner_asset'] = $assets;

    //返回褪底图片数据
    $assets = array();
    $asset_query = array('parent_id'=>$scene_product['_id'], 'asset_type'=>121);
    $asset_options['page'] = 1;
    $asset_options['size'] = 10;
    $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

    if(!empty($asset_result['rows'])){
      foreach($asset_result['rows'] as $key=>$value){
        array_push($assets, array('url'=>$value['thumbnails']['hd']['view_url'],'width'=>$value['width'],'height'=>$value['height']));
      }
    }
    $data['png_asset'] = $assets;

    $data['cover_url'] = $scene_product['cover']['thumbnails']['apc']['view_url'];
    $data['link'] = htmlspecialchars_decode($data['link']);

    unset($data['banner_asset_ids']);
    unset($data['png_asset_ids']);
    unset($data['asset_ids']);

		return $this->api_json('请求成功', 0, $data);
	}


  /**
   * 添加产品
   */
  public function add(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
 		  return $this->api_json('请先登录', 3000);   
    }

    $title = isset($this->stash['title']) ? $this->stash['title'] : null;
    // 原文ID
    $oid = isset($this->stash['oid']) ? $this->stash['oid'] : null;
    $sku_id = isset($this->stash['sku_id']) ? $this->stash['sku_id'] : null;
    // 来源
    $attrbute = isset($this->stash['attrbute']) ? (int)$this->stash['attrbute'] : 0;
    // 默认用户创建
    $kind = 2;
    $market_price = isset($this->stash['market_price']) ? (float)$this->stash['market_price'] : 0;
    $sale_price = isset($this->stash['sale_price']) ? (float)$this->stash['sale_price'] : 0;
    // 原文链接
    $link = isset($this->stash['link']) ? $this->stash['link'] : null;
    $published = isset($this->stash['published']) ? (int)$this->stash['published'] : 1;
    // 封面图
    $cover_url = isset($this->stash['cover_url']) ? $this->stash['cover_url'] : null;
    // Banner图
    $banners_url = isset($this->stash['banners_url']) ? $this->stash['banners_url'] : null;

    if(empty($title) || empty($oid) || empty($market_price) || empty($market_price) || empty($cover_url) || empty($sale_price) || empty($link) || empty($attrbute)){
  		return $this->api_json('缺少请求参数', 3001);
    }

    // 先保存图片，再生成Asset，防止图片不能及时加载
    // 处理图片cover
    $qiniu_file = @file_get_contents($cover_url);
		$image_info = Sher_Core_Util_Image::image_info_binary($qiniu_file);
		if($image_info['stat']==0){
			return $this->api_json($image_info['msg'], 3002);
		}
    $qiniu_param = array(
      'domain' => Sher_Core_Util_Constant::STROAGE_SCENE_PRODUCT,
      'asset_type' => Sher_Core_Model_Asset::TYPE_GPRODUCT,
      'user_id' => $user_id,
      'filename' => null,
      'image_info' => $image_info,
    );
		$cover_result = Sher_Core_Util_Image::api_image($qiniu_file, $qiniu_param);
		if($cover_result['stat']){
			$cover_id = $cover_result['asset']['id'];
		}else{
			$cover_id = null; 
		}

    // 处理Banners
    $banner_asset_ids = array();
    if(!empty($banners_url)){
      $qiniu_param['asset_type'] = Sher_Core_Model_Asset::TYPE_GPRODUCT_BANNER;
      if(is_array($banners_url)){
        $banner_arr = $banners_url;
      }else{
        $banner_arr = explode('&&', $banners_url);
      }
      for($i=0;$i<count($banner_arr);$i++){
        $b_url = $banner_arr[$i];
        $b_file = @file_get_contents($b_url);
		    $b_image_info = Sher_Core_Util_Image::image_info_binary($b_file);
        if($b_image_info['stat']==0){
          continue;
        }
        $b_result = Sher_Core_Util_Image::api_image($b_file, $qiniu_param);
        if($b_result['stat']){
          $banner_id = $b_result['asset']['id'];
          array_push($banner_asset_ids, $banner_id);
        }else{
          continue;
        }
      } // endfor
    } // endif empty banners_url

    $rows = array(
      'user_id' => $user_id,
      'title' => $title,
      'oid' => $oid,
      'cover_id' => $cover_id,
      'banner_asset_ids' => $banner_asset_ids,
      'sku_id' => $sku_id,
      'attrbute' => $attrbute,
      'kind' => $kind,
      'market_price' => $market_price,
      'sale_price' => $sale_price,
      'link' => $link,
      'published' => $published,
    );

    $scene_product_model = new Sher_Core_Model_SceneProduct();
    $ok = $scene_product_model->apply_and_save($rows);
    if($ok){
      $id = $scene_product_model->id;

      // 更新封面信息
      if($rows['cover_id']){
        $scene_product_model->update_batch_assets(array($rows['cover_id']), $id);
      }
      // 更新Banner信息
      if(!empty($rows['banner_asset_ids'])){
        $scene_product_model->update_batch_assets($rows['banner_asset_ids'], $id);
      }

    	return $this->api_json('创建失败!', 0, array('id'=>$id)); 
    }else{
   	  return $this->api_json('创建失败!', 3003);
    }

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
   * 站外商品查询--淘宝
   */
  public function tb_view(){
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
      $result = Sher_Core_Util_JdSdk::search_by_item($ids, $options);  
    }else{
      return $this->api_json('搜索类型不正确!', 3002);
    }

    if($result['success']){
      $row = array();
      $row['rows'] = array();
      $row['total_rows'] = count($result['data']['results']['n_tbk_item']);
      // 整理数据
      for($i=0;$i<count($result['data']['results']['n_tbk_item']);$i++){
        $obj = $result['data']['results']['n_tbk_item'][$i];
        $row['rows'][$i]['oid'] = $obj['num_iid'];
        $row['rows'][$i]['title'] = $obj['title'];
        $row['rows'][$i]['cover_url'] = $obj['pict_url'];
        $row['rows'][$i]['banners_url'] = $obj['small_images']['string'];
        $row['rows'][$i]['market_price'] = $obj['reserve_price'];
        $row['rows'][$i]['sale_price'] = $obj['zk_final_price'];
        $row['rows'][$i]['link'] = $obj['item_url'];
      }
      return $this->api_json('success', 0, $row);
    }else{
      return $this->api_json($result['msg'], 3005);
    }
  
  }

  /*
   * 站外商品查询--京东
   */
  public function jd_view(){
    $result = $options = array();
    $ids = isset($this->stash['ids']) ? $this->stash['ids'] : null;

    if(empty($ids)){
      return $this->api_json('缺少请求参数!', 3001);    
    }

    $options = array();

    $result = Sher_Core_Util_JdSdk::search_by_item($ids, $options);  

    if($result['success']){
      $row = array();
      $row['rows'] = array();
      $row['total_count'] = count($result['data']['listproductbase_result']);
      // 整理数据
      for($i=0;$i<count($result['data']['listproductbase_result']);$i++){
        $obj = $result['data']['listproductbase_result'][$i];
        $row['rows'][$i]['oid'] = $obj['skuId'];
        $row['rows'][$i]['title'] = $obj['pname'];
        $row['rows'][$i]['cover_url'] = $obj['imagePath'];
        $row['rows'][$i]['banners_url'] = $obj['banners_url'];
        $row['rows'][$i]['market_price'] = $obj['market_price'];
        $row['rows'][$i]['sale_price'] = $obj['sale_price'];
        $row['rows'][$i]['link'] = $obj['url'];
      }
      return $this->api_json('success', 0, $row);
    }else{
      return $this->api_json($result['msg'], 3005);
    }

  }

  /*
   * 站外商品价格查询--京东
   */
  public function jd_item_price(){
    $result = $options = array();
    $sku_id = isset($this->stash['sku_id']) ? $this->stash['sku_id'] : null;

    if(empty($sku_id)){
      return $this->api_json('缺少请求参数!', 3001);    
    }

    $options = array();

    $result = Sher_Core_Util_JdSdk::search_by_item_price($sku_id, $options);  

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

  /**
   * 从场景跳出统计
   */
  public function sight_click_stat(){
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    if(empty($id)){
      return $this->api_json('缺少请求参数!', 3000);  
    }
    $scene_product_model = new Sher_Core_Model_SceneProduct();
    $scene_product_model->inc_counter('buy_count', 1, $id);
    return $this->api_json('success', 0, array('id'=>$id));
  }

  /**
   * 删除产品
   */
  public function deleted(){
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    if(empty($id)){
      return $this->api_json('缺少请求参数!', 3000);  
    }
    $user_id = $this->current_user_id;

		try{
			$scene_product = new Sher_Core_Model_SceneProduct();
			
      $product = $scene_product->load($id);
      if(empty($product)){
        return $this->api_json('删除的内容不存在！', 3001);       
      }
      if($product['user_id'] != $user_id){
        return $this->api_json('没有权限！', 3002);        
      }
      if($product['kind'] != 2){
        return $this->api_json('不允许删除操作！', 3003);        
      }
      
      $scene_product->remove($id);
      $scene_product->mock_after_remove($id, $product);

		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3004);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
  }
	

}

