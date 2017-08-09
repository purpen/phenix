<?php
/**
 * WAPI 商品接口
 * @author tianshuai
 */
class Sher_WApi_Action_Product extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'getlist', 'view', 'product_category_stick');

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
		
		// 请求参数
    $category_ids = isset($this->stash['category_ids']) ? $this->stash['category_ids'] : 0;
    $wx_category_ids = isset($this->stash['wx_category_ids']) ? $this->stash['wx_category_ids'] : 0;
		$category_tags = isset($this->stash['category_tags']) ? $this->stash['category_tags'] : null;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
			
		$query   = array();
		$options = array();

        // 查询条件
		if($category_ids){
            $category_ids_arr = explode(',', $category_ids);
            $query['category_ids'] = (int)$category_ids;
		}
		if($wx_category_ids){
      $wx_category_arr = array();
      $wx_category_ids_arr = explode(',', $wx_category_ids);
      for ($i=0;$i<count($wx_category_ids_arr);$i++){
        array_push($wx_category_arr, (int)$wx_category_ids_arr[$i]);
      }
      $query['wx_category_ids'] = array('$in' => $wx_category_arr);
		}
        
        if($category_tags){
            $category_tag_arr = explode(',', $category_tags);
			$query['category_ids'] = (int)$category_ids;
        }

        // 阶段
        $query['stage'] = 20;

		// 已发布上线
		$query['published'] = 1;
        $query['deleted'] = 0;
		
		if($stick){
			$query['stick'] = 1;
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
				$options['sort_field'] = 'stick';
				break;
			case 5:
				$options['sort_field'] = 'featured';
				break;
			case 6:
				$options['sort_field'] = 'price';
				break;
			case 7:
				$options['sort_field'] = 'price_asc';
				break;
		}

		$some_fields = array(
            '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
            'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1,
			'cover_id'=>1, 'wx_category_ids'=>1, 'stage'=>1, 'inventory'=>1,
            'stick'=>1, 'featured'=>1, 'love_count'=>1, 'favorite_count'=>1, 'view_count'=>1,
            'deleted'=>1,
		);
		
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
            if($data[$i]['category_tags']==0){
                $data[$i]['category_tags'] = array();
            }
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['apc']['view_url'];

            // 保留2位小数
            $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);

            unset($data[$i]['stage']);
            unset($data[$i]['deleted']);

		} // endfor
		$result['rows'] = $data;
		
		return $this->wapi_json('请求成功', 0, $result);
	}
	
	/**
	 * 商品详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->wapi_json('访问的产品不存在！', 3000);
		}

		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);

        if(empty($product)) {
                return $this->wapi_json('访问的产品不存在！', 3000);
        }
        $product = $model->extended_model_row($product);

		if($product['deleted']){
			return $this->wapi_json('访问的产品不存在或已被删除！', 3001);
		}

		if(!$product['published']){
			return $this->wapi_json('访问的产品已下架！', 3002);
		}

		$some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_ids', 'stage', 'summary', 'tags', 'tags_s', 'category_tags',
			'inventory', 'snatched', 'wap_view_url', 'brand_id', 'brand', 'content_wap', 'content',
            'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
		);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		$model->inc_counter('true_view_count', 1, $id);

		// 重建数据结果
		$data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($product[$key]) ? $product[$key] : null;
        }

        $asset_service = Sher_Core_Service_Asset::instance();

        //返回图片数据
        $assets = array();
        $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>11);
        $asset_options['page'] = 1;
        $asset_options['size'] = 6;
        $asset_options['sort_field'] = 'latest';

        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
          foreach($asset_result['rows'] as $key=>$value){
            array_push($assets, $value['thumbnails']['apc']['view_url']);
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
			'stage' => Sher_Core_Model_Inventory::STAGE_SHOP,
		));

        if(!empty($skus)){
            for($k=0;$k<count($skus);$k++){
                $skus[$k]['cover_url'] = '';
                if(isset($skus[$k]['cover_id']) && !empty($skus[$k]['cover_id'])){
                    $sku_cover = $inventory->cover($skus[$k]);
                    if($sku_cover){
                        $skus[$k]['cover_url'] = $sku_cover['thumbnails']['apc']['view_url'];
                    }
                }
            }
        }
		$data['skus'] = $skus;
		$data['skus_count'] = count($skus);

        // 品牌
        $brand = null;
        if(isset($data['brand']) && !empty($data['brand'])){
            $brand = array();
            $brand['_id'] = (string)$data['brand']['_id'];
            $brand['title'] = $data['brand']['title'];
            $brand['cover_url'] = $data['brand']['cover']['thumbnails']['huge']['view_url'];
            $brand['banner_url'] = $data['brand']['banner']['thumbnails']['aub']['view_url'];
            $brand['content'] = $data['brand']['des'];
        }
        $data['brand'] = $brand;
        if(isset($data['content_wap']) && !empty($data['content_wap'])){
            $des_images = Sher_Core_Helper_Util::fetch_description_img($data['content_wap']);
            unset($data['content_wap']);
        }

        if(empty($des_images)){
            $des_images = Sher_Core_Helper_Util::fetch_description_img($data['content']);    
        }
        unset($data['content']);
        
        $data['des_images'] = $des_images;

		return $this->wapi_json('请求成功', 0, $data);
	}

  /**
   * 每个分类下推荐4款商品
   */
  public function product_category_stick(){

		$domain = isset($this->stash['domain'])?(int)$this->stash['domain']:1;
		
		$query   = array();
		$options = array();
		
		$query['domain'] = 14;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
    $query['pid'] = 0;
		
    $options['page'] = 1;
    $options['size'] = 4;
    $options['sort_field'] = 'orby';

    $some_fields = array(
      '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1, 'sub_count'=>1,
      'domain'=>1, 'is_open'=>1, 'total_count'=>1, 'state'=>1, 'back_url'=>1,
    );

		$product_some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'wx_category_ids', 'stage', 'comment_star',
      'stick', 'love_count', 'favorite_count', 'view_count', 'tips_label',
      'deleted'=>1,
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
      if ($i < 2) {
        $product_size = 4;
      } else {
        $product_size = 6;
      }
      // 获取该分类下推荐的4款产品
      $products = $product_model->find(array('wx_category_ids'=>$cid, 'stage'=>20, 'published'=>1), array('page'=>1, 'size'=>$product_size, 'sort'=>array('stick'=>-1,'update'=>-1)));

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
		return $this->wapi_json('请求成功', 0, $result);
  
  }

}

