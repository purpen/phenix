<?php
/**
 * 品牌管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SightAndProduct extends Sher_Api_Action_Base {
	
	public $stash = array(
        'page' => 1,
        'size' => 10,
	);
	
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
		
		// http://www.taihuoniao.me/app/api/sight_and_product/getlist?product_id=1
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:1000;
		$sight_id = isset($this->stash['sight_id']) ? (int)$this->stash['sight_id'] : 0;
		$product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
		
		$some_fields = array();
		
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
		
		//$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SightAndProduct::instance();
        $result = $service->get_sight_product_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			if($sight_id){
				$result['rows'][$k] = $result['rows'][$k]['product'];
				$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['apc']['view_url'];
				$filter_fields = array('attrbute_str','strip_description','scene_tags_s','category_tags_s','png_asset_ids','banner_asset_ids','asset_ids','scene_tags');
				for($i=0;$i<count($filter_fields);$i++){
					$key = $filter_fields[$i];
					unset($result['rows'][$k][$key]);
				}
			}
			if($product_id){
				$result['rows'][$k] = $result['rows'][$k]['sight'];
				$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			}
		}
		
		// 过滤多余属性
        $filter_fields  = array('scene','user','user_ext','cover_id','cover','__extend__','category');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
}

