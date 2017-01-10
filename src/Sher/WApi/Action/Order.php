<?php
/**
 * WAPI 订单接口
 * @author tianshuai
 */
class Sher_WApi_Action_Order extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'getlist', 'view');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		// 请求参数
		$category_tags = isset($this->stash['category_tags']) ? $this->stash['category_tags'] : null;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
			
		$query   = array();
		$options = array();

        // 查询条件
        if($category_tags){
          $category_tag_arr = explode(',', $category_tags);
          $query['category_tags'] = array('$in'=>$category_tag_arr);
        }

        // 阶段
        $query['stage'] = 9;

		// 已发布上线
		$query['published'] = 1;
        $query['deleted'] = 0;
		
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
				$options['sort_field'] = 'stick';
				break;
			case 5:
				$options['sort_field'] = 'featured';
				break;
		}

		$some_fields = array(
            '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
            'tags'=>1, 'tags_s'=>1, 'created_on'=>1, 'updated_on'=>1,
			'cover_id'=>1, 'category_ids'=>1, 'stage'=>1, 'inventory'=>1,
            'stick'=>1, 'featured'=>1, 'love_count'=>1, 'favorite_count'=>1, 'view_count'=>1, 'comment_count'=>1, 'category_tags'=>1,
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
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}

		
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
			return $this->api_json('访问的产品已下架！', 3002);
		}

		$some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_ids', 'stage', 'summary', 'tags', 'tags_s', 'category_tags',
			'inventory', 'snatched', 'wap_view_url', 'brand_id', 'brand',
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

		return $this->api_json('请求成功', 0, $data);
	}

}

