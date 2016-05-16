<?php
/**
 * 品牌管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SightAndProduct extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist');

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
		
		$some_fields = array(
			'_id'=>1, 'sight_id'=>1, 'product_id'=>1, 'product_kind'=>1, 'created_on'=>1, 'updated_on'=>1,
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
		
		if(!$sight_id && !$product_id){
			return $this->api_json('请求失败，缺少必要参数!', 3001);
		}
		
		if($sight_id){
			$query['sight_id'] = $sight_id;
		}
		
		if($product_id){
			$query['product_id'] = $product_id;
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
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:null;
			}
			$data[$i]['_id'] = (string)$result['rows'][$i]['_id'];
      $product = $result['rows'][$i]['product'];
      $sight = $result['rows'][$i]['sight'];

      $data[$i]['product'] = array();
      $data[$i]['sight'] = array();
      if(!empty($product)){

        // 重建商品数据结果
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
        // 过滤用户
        $data[$i]['sight']['_id'] = $sight['_id'];
        $data[$i]['sight']['user_info'] = Sher_Core_Helper_FilterFields::wap_user($sight['user']);
        $data[$i]['sight']['cover_url'] = $sight['cover']['thumbnails']['huge']['view_url'];
        $data[$i]['sight']['title'] = $sight['title'];
        $data[$i]['sight']['address'] = $sight['address'];
        $data[$i]['sight']['scene_title'] = isset($sight['scene']) ? $sight['scene']['title'] : null;
      }

		}

    $result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

}

