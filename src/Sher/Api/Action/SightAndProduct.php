<?php
/**
 * 商品/品牌/产品／地盘关系表
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SightAndProduct extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'scene_getlist');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 情景列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$sight_id = isset($this->stash['sight_id']) ? (int)$this->stash['sight_id'] : 0;
		$product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
		$brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : null;
		
		$some_fields = array(
			'_id'=>1, 'sight_id'=>1, 'product_id'=>1, 'product_kind'=>1, 'brand_id'=>1, 'created_on'=>1, 'updated_on'=>1,
		);

		$product_some_fields = array(
            '_id', 'title', 'short_title', 'oid', 'sale_price', 'market_price','brand_id',
			'kind', 'cover_id', 'category_id', 'fid', 'summary', 'link',
			'stick', 'summary', 'fine', 'banner_asset_ids', 'asset_ids', 'category_tags',
			'view_count', 'favorite_count', 'love_count', 'comment_count','buy_count', 'deleted',
            'published', 'attrbute', 'state', 'tags', 'tags_s', 'created_on', 'updated_on', 'created_at',
		);
		
		$query   = array();
		$options = array();
		
		if(!$sight_id && !$product_id && !$brand_id){
			return $this->api_json('请求失败，缺少必要参数!', 3001);
		}
		
		if($sight_id){
			$query['sight_id'] = $sight_id;
		}
		
		if($product_id){
			$query['product_id'] = $product_id;
		}

		if($brand_id){
			$query['brand_id'] = $brand_id;
		}
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
    $service = Sher_Core_Service_SightAndProduct::instance();
    $result = $service->get_sight_product_list($query, $options);


    $asset_service = Sher_Core_Service_Asset::instance();
		$data = array();
		// 重建数据结果
		for($i=0;$i<count($result['rows']);$i++){
      $product = $result['rows'][$i]['product'];
      $sight = $result['rows'][$i]['sight'];
      if(empty($product) || empty($sight)){
        //continue;
      }

      $current_user_id = $this->current_user_id;

		$favorite_model = new Sher_Core_Model_Favorite();
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:null;
			}
			$data[$i]['_id'] = (string)$result['rows'][$i]['_id'];

      $data[$i]['product'] = null;
      $data[$i]['sight'] = null;
      if(!empty($product)){
        // 重建商品数据结果
        $data[$i]['product'] = array();
        for($j=0;$j<count($product_some_fields);$j++){
          $product_key = $product_some_fields[$j];
          $data[$i]['product'][$product_key] = isset($product[$product_key]) ? $product[$product_key] : null;
        }

        // 封面图url
			  $data[$i]['product']['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
        //返回Banner图片数据
        $assets = array();
        $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>120);
        $asset_options['page'] = 1;
        $asset_options['size'] = 10;
        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
          foreach($asset_result['rows'] as $banner_key=>$banner_value){
            array_push($assets, $banner_value['thumbnails']['aub']['view_url']);
          }
        }
        $data[$i]['product']['banner_asset'] = $assets;
      }

      if(!empty($sight)){
        $data[$i]['sight'] = array();
        // 过滤用户
        $user = array();
        
        if($sight['user']){
            $user['user_id'] = $sight['user']['_id'];
            $user['nickname'] = $sight['user']['nickname'];
            $user['avatar_url'] = $sight['user']['medium_avatar_url'];
            $user['summary'] = $sight['user']['summary'];
            $user['counter'] = $sight['user']['counter'];
            $user['follow_count'] = $sight['user']['follow_count'];
            $user['fans_count'] = $sight['user']['fans_count'];
            $user['love_count'] = $sight['user']['love_count'];
            $user['is_expert'] = isset($sight['user']['identify']['is_expert']) ? (int)$sight['user']['identify']['is_expert'] : 0;
            $user['label'] = isset($sight['user']['profile']['label']) ? $sight['user']['profile']['label'] : '';
            $user['expert_label'] = isset($sight['user']['profile']['expert_label']) ? $sight['user']['profile']['expert_label'] : '';
            $user['expert_info'] = isset($sight['user']['profile']['expert_info']) ? $sight['user']['profile']['expert_info'] : '';
        }

        $data[$i]['sight']['_id'] = $sight['_id'];
        $data[$i]['sight']['user_info'] = $user;
        $data[$i]['sight']['cover_url'] = $sight['cover']['thumbnails']['huge']['view_url'];
        $data[$i]['sight']['title'] = $sight['title'];
        $data[$i]['sight']['address'] = $sight['address'];
        $data[$i]['sight']['scene_title'] = isset($sight['scene']) ? $sight['scene']['title'] : null;


        // 用户是否点赞/收藏
        $is_love = 0;
        if($current_user_id){
            $fav_query = array(
                'target_id' => $sight['_id'],
                'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
                'event' => Sher_Core_Model_Favorite::EVENT_LOVE,
                'user_id' => $current_user_id
            );
            $has_love = $favorite_model->first($fav_query);
            if($has_love) $is_love = 1;

        }
        $data[$i]['sight']['is_love'] = $is_love;

      }

		} // endfor

    $result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 地盘产品列表
	 */
	public function scene_getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		$product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
		
        $current_user_id = $this->current_user_id;

		$some_fields = array(
			'_id'=>1, 'scene_id'=>1, 'product_id'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);

		$product_some_fields = array(
            '_id', 'title', 'short_title', 'oid', 'sale_price', 'market_price','brand_id',
			'kind', 'cover_id', 'category_id', 'fid', 'summary', 'link',
			'stick', 'summary', 'fine', 'banner_asset_ids', 'asset_ids', 'category_tags',
			'view_count', 'favorite_count', 'love_count', 'comment_count','buy_count', 'deleted',
            'published', 'attrbute', 'state', 'tags', 'tags_s', 'created_on', 'updated_on', 'created_at',
		);
		
		$query   = array();
		$options = array();
		
		if(!$scene_id && !$product_id){
			return $this->api_json('请求失败，缺少必要参数!', 3001);
		}
		
		if($scene_id){
			$query['scene_id'] = $scene_id;
		}
		
		if($product_id){
			$query['product_id'] = $product_id;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_ZoneProductLink::instance();
        $result = $service->get_zone_product_list($query, $options);


        $asset_service = Sher_Core_Service_Asset::instance();
		$data = array();
		// 重建数据结果
		for($i=0;$i<count($result['rows']);$i++){
            $row = $result['rows'][$i];
            $product = $result['rows'][$i]['product'];
            $scene = $result['rows'][$i]['scene'];

			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:null;
			}
			$data[$i]['_id'] = (string)$result['rows'][$i]['_id'];

            $data[$i]['product'] = null;
            $data[$i]['scene'] = null;

              if(!empty($product)){
                // 重建商品数据结果
                $data[$i]['product'] = array();
                for($j=0;$j<count($product_some_fields);$j++){
                  $product_key = $product_some_fields[$j];
                  $data[$i]['product'][$product_key] = isset($product[$product_key]) ? $product[$product_key] : null;
                }

                // 封面图url
                      $data[$i]['product']['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
                //返回Banner图片数据
                $assets = array();
                $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>120);
                $asset_options['page'] = 1;
                $asset_options['size'] = 10;
                $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

                if(!empty($asset_result['rows'])){
                  foreach($asset_result['rows'] as $banner_key=>$banner_value){
                    array_push($assets, $banner_value['thumbnails']['aub']['view_url']);
                  }
                }
                $data[$i]['product']['banner_asset'] = $assets;
              }

              if(!empty($scene)){
                $data[$i]['scene'] = array();
              }

		} // endfor

        $result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

}

