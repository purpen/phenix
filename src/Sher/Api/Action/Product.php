<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Product extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'comments', 'fetch_relation_product', 'product_category_stick', 'search', 'snatched_list');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

  /**
   * 每个分类下推荐4款商品
   */
  public function product_category_stick(){

		$domain = isset($this->stash['domain'])?(int)$this->stash['domain']:1;
		
		$query   = array();
		$options = array();
		
		$query['domain'] = 1;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
    $query['sub_count'] = array('$ne'=>0);
		
    $options['page'] = 1;
    $options['size'] = 12;
    $options['sort_field'] = 'orby';

    $some_fields = array(
      '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1, 'sub_count'=>1,
      'domain'=>1, 'is_open'=>1, 'total_count'=>1, 'reply_count'=>1, 'state'=>1, 'app_cover_url'=>1,
    );

		$product_some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_id', 'stage', 'summary', 'comment_star', 'tags', 'tags_s',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
		);
		
    $options['some_fields'] = $some_fields;

    $service = Sher_Core_Service_Category::instance();
    $result = $service->get_category_list($query, $options);

		$product_model = new Sher_Core_Model_Product();

		// 重建数据结果
		$data = array();
    for($i=0;$i<count($result['rows']);$i++){
      $cid = $result['rows'][$i]['_id'];
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}
      // 获取该分类下推荐的4款产品
      $products = $product_model->find(array('category_id'=>$cid, 'stage'=>9, 'approved'=>1, 'published'=>1), array('page'=>1, 'size'=>4, 'sort'=>array('stick'=>-1,'update'=>-1)));

      $product_arr = array();
      for($j=0;$j<count($products);$j++){
        // 重建商品数据结果
        $product_data = array();
        if($products[$j] && empty($products[$j]['deleted'])){
          $product = $product_model->extended_model_row($products[$j]);
          for($k=0;$k<count($product_some_fields);$k++){
            $product_key = $product_some_fields[$k];
            $product_data[$product_key] = isset($product[$product_key]) ? $product[$product_key] : null;
          }
          // 封面图url
          $product_data['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
        }
        // 添加到数组 
        array_push($product_arr, $product_data);       
      } // endfor product
      $data[$i]['products'] = $product_arr;
    } // endfor result[rows]
		$result['rows'] = $data;
		return $this->api_json('请求成功', 0, $result);
  
  }
	
	/**
	 * 商品列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		$some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
      'presale_people'=>1, 'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1,
			'presale_percent'=>1, 'cover_id'=>1, 'category_id'=>1, 'stage'=>1, 'vote_favor_count'=>1,
			'vote_oppose_count'=>1, 'summary'=>1, 'succeed'=>1, 'voted_finish_time'=>1, 'presale_finish_time'=>1,
			'snatched_time'=>1, 'inventory'=>1, 'topic_count'=>1,'presale_money'=>1, 'snatched'=>1,
      'presale_goals'=>1, 'stick'=>1, 'featured'=>1, 'love_count'=>1, 'favorite_count'=>1, 'view_count'=>1, 'comment_count'=>1,
      'comment_star'=>1,'snatched_end_time'=>1, 'snatched_price'=>1, 'snatched_count'=>1,
      // app抢购
      'app_snatched'=>1, 'app_snatched_time'=>1, 'app_snatched_end_time'=>1, 'app_snatched_price'=>1,
      'app_snatched_count'=>1, 'app_appoint_count'=>1,
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
    $product_model = new Sher_Core_Model_Product();
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
      $data[$i]['tips_label'] = 0;
      $data[$i]['content_view_url'] = sprintf('%s/view/product_show?id=%d', Doggy_Config::$vars['app.url.api'], $result['rows'][$i]['_id']);
      // 保留2位小数
      $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);
      // 闪购标识
      $data[$i]['is_app_snatched'] = $product_model->is_app_snatched($result['rows'][$i]);
      if(isset($data[$i]['app_snatched_price']) && !empty($data[$i]['app_snatched_price'])){
        // 保留2位小数
        $data[$i]['app_snatched_price'] = sprintf('%.2f', $result['rows'][$i]['app_snatched_price']);     
      }
      // 新品标识--非闪购产品且一个月内上的产品
      if(empty($data[$i]['is_app_snatched'])){
        if(empty($data[$i]['featured'])){
          $data[$i]['tips_label'] = 2;
        }else{
          $data[$i]['tips_label'] = $data[$i]['created_on']>(time()-2592000) ? 1 : 0;
        }
      }else{
        $data[$i]['tips_label'] = 3;       
      }
		} // endfor
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

		if(!$product['published']){
			return $this->api_json('访问的产品未发布！', 3002);
		}

		$some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_id', 'stage', 'summary', 'tags', 'tags_s',
			'snatched_time', 'inventory', 'snatched', 'wap_view_url',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
      'comment_star','snatched_end_time', 'snatched_price', 'snatched_count',
      // app抢购
      'app_snatched', 'app_snatched_time', 'app_snatched_end_time', 'app_snatched_price', 'app_snatched_count', 'app_appoint_count',
		);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('app_view_count', 1, $id);

		// 重建数据结果
		$data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($product[$key]) ? $product[$key] : null;
    }

    //转换描述格式
    $data['content_view_url'] = sprintf('%s/view/product_show?id=%d', Doggy_Config::$vars['app.url.api'], $product['_id']);

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
    $asset_options['sort_field'] = 'latest';
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
		$data['can_saled'] = $model->app_can_saled($product);
		
		// 获取skus及inventory
		$inventory = new Sher_Core_Model_Inventory();
		$skus = $inventory->find(array(
			'product_id' => $id,
			'stage' => $product['stage'],
		));
		$data['skus'] = $skus;
		$data['skus_count'] = count($skus);

    // 闪购标识
    $data['is_app_snatched'] = $model->is_app_snatched($product);
    // 闪购进度
    $data['app_snatched_stat'] = $model->app_snatched_stat($product);
    // 返回闪购时间戳差，如果非闪购或已结束，返回0
    if($data['app_snatched_stat']==1){
      $data['app_snatched_time_lag'] = $product['app_snatched_time'] - time();
    }elseif($data['app_snatched_stat']==2){
      $data['app_snatched_time_lag'] = $product['app_snatched_end_time'] - time();
    }else{
      $data['app_snatched_time_lag'] = 0;
    }

    // 用户是否设置闪购提醒
    $data['is_app_snatched_alert'] = 0;
    if($data['is_app_snatched'] && $user_id){
      $support_model = new Sher_Core_Model_Support();
      $has_one = $support_model->check_voted($user_id, $data['_id'], Sher_Core_Model_Support::EVENT_APP_ALERT);
      if($has_one){
        $data['is_app_snatched_alert'] = 1; 
      }
    }

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

	
	/**
	 * 用户评论列表
	 */
	public function comments(){
		$type = Sher_Core_Model_Comment::TYPE_PRODUCT;
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		// 请求参数
        $user_id = $this->current_user_id;
        $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		if(empty($target_id)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($user_id){
            $query['user_id'] = (int) $user_id;
        }
		if($target_id){
			$query['target_id'] = (string)$target_id;
		}
		if($type){
			$query['type'] = $type;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'earliest';
		
		// 开启查询
        $service = Sher_Core_Service_Comment::instance();
        $result = $service->get_comment_list($query, $options);

        for($i=0;$i<count($result['rows']);$i++){
          if($result['rows'][$i]['user']){
            $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
          }
          if($result['rows'][$i]['target_user']){
            $result['rows'][$i]['target_user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['target_user']);
          }
        }
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 商品评价
	 */
	public function ajax_comment(){

    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);
    }
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : 0;
    if(empty($rid)){
      return $this->api_json('缺少请求参数!', 3001);
    }
		
		try{
      $arr = isset($this->stash['array']) ? $this->stash['array'] : array();
      if(empty($arr)){
        return $this->api_json('缺少商品内容!', 3002);   
      }
      $item_arr = json_decode($this->stash['array'], true);
      if(!is_array($item_arr)){
        return $this->api_json('参数类型错误!', 3003);    
      }

      $orders_model = new Sher_Core_Model_Orders();
      $order = $orders_model->find_by_rid($rid);
      if(empty($order)){
        return $this->api_json('订单不存在!', 3004);     
      }
      // 是否是当前用户
      if($order['user_id'] != $user_id){
        return $this->api_json('没有权限!', 3005);      
      }
      // 是否是待评价订单
      if ($order['status'] != Sher_Core_Util_Constant::ORDER_EVALUATE){
        return $this->api_json('订单状态不正确！', 3006);
      }

      // 默认ios
      $from_site = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 3;

			// 保存数据
      $comment_model = new Sher_Core_Model_Comment();

      // 获取订单商品ID
      $product_arr = array();
      foreach($order['items'] as $k=>$v){
        array_push($product_arr, (int)$v['product_id']);
      }

      // 循环要评价的商品
      foreach($item_arr as $k=>$v){
        $product_id = (int)$v['target_id'];
        $sku_id = (int)$v['sku_id'];
        $content = $v['content'];
        $star = (int)$v['star'];
        if(!in_array($star, array(1,2,3,4,5))){
          $star = 0;
        }

        if(in_array($product_id, $product_arr)){
          if(empty($star) || empty($content)){
            return $this->api_json('评论内容不能为空！', 3010); 
          }

          // 验证数据
          $comment_data = array(
            'target_id' => (string)$product_id,
            'content' => $content,
            'star' => $star,
            'user_id' => $user_id,
            'type' => Sher_Core_Model_Comment::TYPE_PRODUCT,
            'sku_id' => $sku_id,
            'from_site' => $from_site,
          );
          $comment_ok = $comment_model->apply_and_save($comment_data);
        }
      } //endfor

		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3008);
		}

    $order_ok = $orders_model->finish_order((string)$order['_id']);
    if(!$order_ok){
      return $this->api_json('操作失败！', 3009);   
    }
		
		return $this->api_json('操作成功', 0, array('rid'=>$rid));
	}

	/**
	 * 获取推荐产品
	 */
	public function fetch_relation_product(){
		$sword = isset($this->stash['sword']) ? $this->stash['sword'] : null;
    $current_id = isset($this->stash['current_id']) ? (int)$this->stash['current_id'] : 0;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 4;

		$some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_id', 'stage', 'summary', 'tags', 'tags_s',
			'comment_star', 'inventory', 'snatched',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
		);
		
		$result = array();
		$options = array(
			'page' => 1,
			'size' => $size,
			'sort_field' => 'latest',
      // 最新
      'sort' => 1,
      'evt' => 'tag',
      't' => 7,
      'oid' => $current_id,
      'type' => 1,
		);        
		if(!empty($sword)){
      $xun_arr = Sher_Core_Util_XunSearch::search($sword, $options);
      if($xun_arr['success'] && !empty($xun_arr['data'])){
        $product_mode = new Sher_Core_Model_Product();
        $items = array();
        foreach($xun_arr['data'] as $k=>$v){
          $product = $product_mode->extend_load((int)$v['oid']);
          if(!empty($product)){

              // 重建数据结果
              $data = array();
              for($i=0;$i<count($some_fields);$i++){
                $key = $some_fields[$i];
                $data[$key] = isset($product[$key]) ? $product[$key] : null;
              }
            // 封面图url
            $data['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
            array_push($items, $data);
          }
        }
        $result = $items;
      }else{
        $result = array();
      }

		}
    if(empty($result)){
      return $this->api_json('没有找到相关商品', 0, $result);
    }
    
		return $this->api_json('操作成功', 0, array('total_rows'=>$xun_arr['total_count'], 'rows'=>$result, 'total_page'=>$xun_arr['total_page']));
	}

  /**
   * 闪购添加提醒操作
   */
  public function app_snatched_add_alert(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);
    }
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 3;
    if(empty($target_id)){
      return $this->api_json('缺少请求参数!', 3001);
    }

    $product_model = new Sher_Core_Model_Product();
    $product = $product_model->load($target_id);
    if(empty($product)){
      return $this->api_json('商品不存在!', 3004);    
    }
    $snatched_stat = $product_model->app_snatched_stat($product);
    if($snatched_stat==1){
      // 提前10分钟不能提醒
      $begin_time = $product['app_snatched_time'];
      if(time()>($begin_time-600)){
        return $this->api_json('等待开抢!', 3005);     
      }
    }else{
      return $this->api_json('提醒活动已结束!', 3006);    
    }

    $support_model = new Sher_Core_Model_Support();
    $has_one = $support_model->check_voted($user_id, $target_id, Sher_Core_Model_Support::EVENT_APP_ALERT);
    if($has_one){
      return $this->api_json('不能重复添加!', 3002);    
    }
    $row = array(
      'user_id' => $user_id,
      'target_id' => $target_id,
      'event' => $event,
    );
    $ok = $support_model->apply_and_save($row);
    if($ok){
      $id = (string)$support_model->id;
      return $this->api_json('success!', 0, array('id'=>$id));   
    }else{
      return $this->api_json('添加提醒失败!', 3003);   
    }
  }

	/**
	 * 商品秒杀列表
	 */
	public function snatched_list(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;

    $user_id = $this->current_user_id;
		
		$some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
      'presale_people'=>1, 'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1,
			'presale_percent'=>1, 'cover_id'=>1, 'category_id'=>1, 'stage'=>1, 'vote_favor_count'=>1,
			'vote_oppose_count'=>1, 'summary'=>1, 'succeed'=>1, 'voted_finish_time'=>1, 'presale_finish_time'=>1,
			'snatched_time'=>1, 'inventory'=>1, 'topic_count'=>1,'presale_money'=>1, 'snatched'=>1,
      'presale_goals'=>1, 'stick'=>1, 'love_count'=>1, 'favorite_count'=>1, 'view_count'=>1, 'comment_count'=>1,
      'comment_star'=>1,'snatched_end_time'=>1, 'snatched_price'=>1, 'snatched_count'=>1,
      // app抢购
      'app_snatched'=>1, 'app_snatched_time'=>1, 'app_snatched_end_time'=>1, 'app_snatched_price'=>1,
      'app_snatched_count'=>1, 'app_appoint_count'=>1,
		);
		
		// 请求参数

		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		
		$stage = isset($this->stash['stage']) ? (int)$this->stash['stage'] : Sher_Core_Model_Product::STAGE_SHOP;
			
		$query   = array();
		$options = array();
		
		// 查询条件
    $query['app_snatched'] = 1;
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
		$options['sort_field'] = 'app_snatched';
		
		$options['some_fields'] = $some_fields;
		// 开启查询
    $product_model = new Sher_Core_Model_Product();
    $support_model = new Sher_Core_Model_Support();
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
      // 闪购标识
      $data[$i]['is_app_snatched'] = $product_model->is_app_snatched($result['rows'][$i]);
      if(isset($data[$i]['app_snatched_price']) && !empty($data[$i]['app_snatched_price'])){
        // 保留2位小数
        $data[$i]['app_snatched_price'] = sprintf('%.2f', $result['rows'][$i]['app_snatched_price']);     
      }

      // 闪购进度
      $data[$i]['app_snatched_stat'] = $product_model->app_snatched_stat($result['rows'][$i]);
      // 返回闪购时间戳差，如果非闪购或已结束，返回0
      if($data[$i]['app_snatched_stat']==1){
        $data[$i]['app_snatched_time_lag'] = $result['rows'][$i]['app_snatched_time'] - time();
      }elseif($data[$i]['app_snatched_stat']==2){
        $data[$i]['app_snatched_time_lag'] = $result['rows'][$i]['app_snatched_end_time'] - time();
      }else{
        $data[$i]['app_snatched_time_lag'] = 0;
      }

      // 用户是否设置闪购提醒
      $data[$i]['is_app_snatched_alert'] = 0;
      if($data[$i]['is_app_snatched'] && $user_id){
        $has_one = $support_model->check_voted($user_id, $data[$i]['_id'], Sher_Core_Model_Support::EVENT_APP_ALERT);
        if($has_one){
          $data[$i]['is_app_snatched_alert'] = 1; 
        }
      }

		} // endfor
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	

}

