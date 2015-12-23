<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Product extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'comments', 'fetch_relation_product', 'product_category_stick');

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
		
    $options['page'] = 1;
    $options['size'] = 4;
    $options['sort_field'] = 'orby';

    $some_fields = array(
      '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1,
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
      $products = $product_model->find(array('category_id'=>$cid, 'approved'=>1, 'published'=>1), array('page'=>1, 'size'=>4, 'sort'=>array('update'=>-1, 'stick'=>-1)));

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
          $product_data['cover_url'] = $product['cover']['thumbnails']['medium']['view_url'];
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
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['medium']['view_url'];
			// 用户信息
      if(isset($result['rows'][$i]['designer'])){
        $data[$i]['username'] = $result['rows'][$i]['designer']['nickname'];
        $data[$i]['small_avatar_url'] = $result['rows'][$i]['designer']['small_avatar_url'];     
      }

            $data[$i]['content_view_url'] = sprintf('%s/view/product_show?id=%d&current_user_id=%d', Doggy_Config::$vars['app.domain.base'], $result['rows'][$i]['_id'], $this->current_user_id);
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
			'snatched_time', 'inventory', 'can_saled', 'snatched',
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
    $data['content_view_url'] = sprintf('%s/app/api/view/product_show?id=%d&current_user_id=%d', Doggy_Config::$vars['app.domain.base'], $product['_id'], $user_id);

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $data['is_favorite'] = $fav->check_favorite($this->current_user_id, $product['_id'], 1) ? 1 : 0;
    $data['is_love'] = $fav->check_loved($this->current_user_id, $product['_id'], 1) ? 1 : 0;
    $data['is_try'] = empty($product['is_try'])?0:1;

    //返回图片数据
    $assets = array();
    if(!empty($product['asset'])){
      $asset_model = new Sher_Core_Model_Asset();
      $imgs = $asset_model->extend_load_all($product['asset']);
			foreach($imgs as $key=>$value){
        //echo $value['thumbnails']['medium']['view_url'];
        if($value['_id']==$product['cover_id']){
          $cover_img_url = $value['thumbnails']['medium']['view_url'];
        }else{
          array_push($assets, $value['thumbnails']['medium']['view_url']);
        }
      }
      //封面图放第一
      array_unshift($assets, $cover_img_url);
    }
    $data['asset'] = $assets;
    $data['cover_url'] = $cover_img_url;
		
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

		return $this->api_json('请求成功', 0, $data);
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
    $id = $this->stash['id'];
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_PRODUCT;
			
			$model = new Sher_Core_Model_Favorite();
			if(!$model->check_favorite($this->current_user_id, $id, $type)){
				$fav_info = array('type' => $type);
				$ok = $model->add_favorite($this->current_user_id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}

	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
    $id = $this->stash['id'];
    $user_id = $this->current_user_id;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_PRODUCT;
			
			$model = new Sher_Core_Model_Favorite();
			if($model->check_favorite($user_id, $id, $type)){
				$ok = $model->remove_favorite($user_id, $id, $type);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}
	
	/**
	 * 点赞
	 */
	public function ajax_love(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_PRODUCT;
			
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_loved($this->current_user_id, $id, $type)) {
				$love_info = array('type' => $type);
				$ok = $model->add_love($this->current_user_id, $id, $love_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}

	/**
	 * 取消点赞
	 */
	public function ajax_cancel_love(){
    $id = $this->stash['id'];
    $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录!', 3005);
		}
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_PRODUCT;
			
			$model = new Sher_Core_Model_Favorite();
			if ($model->check_loved($user_id, $id, $type)) {
				$love_info = array('type' => $type);
				$ok = $model->cancel_love($user_id, $id, $type);
        if($ok){
        	// 获取计数
          $love_count = $this->remath_count($id, 'love_count');
          return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
        }else{
          return $this->api_json('操作失败', 3003);
        }
      }else{
        return $this->api_json('已点赞', 3004);     
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		

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
	 * 用户评价
	 */
	public function ajax_comment(){
		$data = array();
		$result = array();
		
		try{
			// 验证数据
			$data['target_id'] = $this->stash['target_id'];
			$data['content'] = $this->stash['content'];
			$data['star'] = $this->stash['star'];
			if(empty($data['target_id']) || empty($data['content'])){
				return $this->api_json('获取数据错误,请重新提交', 3000);
			}
		
			$data['user_id'] = $this->current_user_id;
			$data['type'] = Sher_Core_Model_Comment::TYPE_PRODUCT;
			
			// 保存数据
			$model = new Sher_Core_Model_Comment();
			$ok = $model->apply_and_save($data);
			if($ok){
				$comment_id = $model->id;
				$result['comment'] = &$model->extend_load($comment_id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('操作成功', 0, $result);
	}
	
	/**
	 * 计算总数
	 */
	protected function remath_count($id, $field='favorite_count'){
		$count = 0;
		
		$model = new Sher_Core_Model_Product();
		$result = $model->load((int)$id);
		
		if(!empty($result)){
			$count = $result[$field];
		}
		
		return $count;
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
			'cover_id', 'category_id', 'stage', 'summary',
			'comment_star', 'inventory', 'can_saled', 'snatched',
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
            $data['cover_url'] = $product['cover']['thumbnails']['medium']['view_url'];
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

		return $this->api_json('操作成功', 0, $result);
	}
	
}

