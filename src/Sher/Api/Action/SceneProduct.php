<?php
/**
 * 情景导购产品
 * @author tianshuai
 */
class Sher_Api_Action_SceneProduct extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view');

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
		
		$some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
      'presale_people'=>1, 'tags'=>1, 'tags_s'=>1,
			'presale_percent'=>1, 'cover_id'=>1, 'category_id'=>1, 'stage'=>1, 'vote_favor_count'=>1,
			'vote_oppose_count'=>1, 'summary'=>1, 'succeed'=>1, 'voted_finish_time'=>1, 'presale_finish_time'=>1,
			'snatched_time'=>1, 'inventory'=>1, 'can_saled'=>1, 'topic_count'=>1,'presale_money'=>1, 'snatched'=>1,
      'presale_goals'=>1, 'stick'=>1, 'love_count'=>1, 'favorite_count'=>1, 'view_count'=>1, 'comment_count'=>1,
      'comment_star'=>1,'snatched_end_time'=>1, 'snatched_price'=>1, 'snatched_count'=>1,
		);
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		$stage = isset($this->stash['stage']) ? (int)$this->stash['stage'] : Sher_Core_Model_Product::STAGE_SHOP;
			
		$query   = array();
		$options = array();
		
		// 查询条件
		if($category_id){
			$query['category_id'] = (int)$category_id;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		// 状态
		$query['stage'] = $stage;
		// 已审核
		$query['approved']  = 1;
		// 已发布上线
		$query['published'] = 1;
		
		if($stick){
			$query['stick'] = $stick;
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
				$options['sort_field'] = 'vote';
				break;
			case 2:
				$options['sort_field'] = 'love';
				break;
			case 3:
				$options['sort_field'] = 'comment';
				break;
			case 4:
				$options['sort_field'] = 'stick:update';
				break;
			case 5:
				$options['sort_field'] = 'featured:update';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
        $service = Sher_Core_Service_Product::instance();
        $result = $service->get_product_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['apc']['view_url'];
			// 用户信息
      if(isset($result['rows'][$i]['designer'])){
        $data[$i]['username'] = $result['rows'][$i]['designer']['nickname'];
        $data[$i]['small_avatar_url'] = $result['rows'][$i]['designer']['small_avatar_url'];     
      }

            $data[$i]['content_view_url'] = sprintf('%s/view/product_show?id=%d', Doggy_Config::$vars['app.url.api'], $result['rows'][$i]['_id']);
            // 保留2位小数
            $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 商品详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}

    $user_id = $this->current_user_id;
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);

    if(empty($product)) {
			return $this->api_json('访问的产品不存在！', 3000);
    }
    $product = $model->extended_model_row($product);

		if($product['deleted']){
			return $this->api_json('访问的产品不存在或已被删除！', 3001);
		}

		$some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_id', 'stage', 'summary', 'tags', 'tags_s',
			'snatched_time', 'inventory', 'can_saled', 'snatched', 'wap_view_url',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
      'comment_star','snatched_end_time', 'snatched_price', 'snatched_count',
		);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		// 重建数据结果
		$data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($product[$key]) ? $product[$key] : null;
    }

    //转换描述格式
    $data['content_view_url'] = sprintf('%s/view/product_show?id=%d', Doggy_Config::$vars['app.domain.base'], $product['_id']);

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $data['is_favorite'] = $fav->check_favorite($this->current_user_id, $product['_id'], 1) ? 1 : 0;
    $data['is_love'] = $fav->check_loved($this->current_user_id, $product['_id'], 1) ? 1 : 0;
    $data['is_try'] = empty($product['is_try'])?0:1;
    // 分享内容
    $data['share_view_url'] = $data['wap_view_url'];
    $data['share_desc'] = $data['advantage'];

    //返回图片数据
    $assets = array();
    $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>11);
    $asset_options['page'] = 1;
    $asset_options['size'] = 10;
    $asset_service = Sher_Core_Service_Asset::instance();
    $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

    if(!empty($asset_result['rows'])){
      foreach($asset_result['rows'] as $key=>$value){
        array_push($assets, $value['thumbnails']['aub']['view_url']);
      }
    }
    $data['asset'] = $assets;
    $data['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
		
		// 验证是否还有库存
		$data['can_saled'] = $model->can_saled($product);
		
		// 获取skus及inventory
		$inventory = new Sher_Core_Model_Inventory();
		$skus = $inventory->find(array(
			'product_id' => $id,
			'stage' => $product['stage'],
		));
		$data['skus'] = $skus;
		$data['skus_count'] = count($skus);

    // 相关推荐产品
		$sword = $data['tags_s'];
    $r_items = array();
		$r_options = array(
			'page' => 1,
			'size' => 3,
			'sort_field' => 'latest',
      // 最新
      'sort' => 1,
      'evt' => 'tag',
      't' => 7,
      'oid' => $data['_id'],
      'type' => 1,
		);        
		if(!empty($sword)){
      $xun_arr = Sher_Core_Util_XunSearch::search($sword, $r_options);
      if($xun_arr['success'] && !empty($xun_arr['data'])){
        foreach($xun_arr['data'] as $k=>$v){
          $r_product = $model->extend_load((int)$v['oid']);
          if(!empty($r_product)){
              // 重建数据结果
              $r_data = array();
              for($i=0;$i<count($some_fields);$i++){
                $key = $some_fields[$i];
                $r_data[$key] = isset($r_product[$key]) ? $r_product[$key] : null;
              }
            // 封面图url
            $r_data['cover_url'] = $r_product['cover']['thumbnails']['apc']['view_url'];
            array_push($r_items, $r_data);
          }
        }
      }

		}

		$data['relation_products'] = $r_items;
		return $this->api_json('请求成功', 0, $data);
	}


  /*
   * 站外商品查询
   */
  public function outside_view(){
  
  
  }

	

}

