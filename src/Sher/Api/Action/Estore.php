<?php
/**
 * 线下店铺接口
 * @author purpen
 */
class Sher_Api_Action_Estore extends Sher_Api_Action_Base {
	
  protected $filter_user_method_list = array('execute','get_store_list','find_stores','get_single_store','get_city_list','get_estore_product_list');

	/**
	 * 默认方法
	 */
	public function execute() {
        
		return $this->get_store_list();
	}
    
    /**
     * 搜索店铺
     */
    public function find_stores() {
        
    }
    
    /**
     * 获取店铺列表
     */
    public function get_store_list() {
		
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 10;
        
        $approved = isset($this->stash['approved']) ? (int)$this->stash['approved'] : 2; // 是否审核
        // 基于地理位置的查询，从城市内查询
        $distance = isset($this->stash['dis']) ? (int)$this->stash['dis'] : 0; // 距离、半径
        $lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0; // 经度
        $lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0; // 纬度
        
        // 判断查询条件
		$query   = array();
		$options = array();
        
        // 必须添加索引 db.estore.ensureIndex({location: "2dsphere"})
        
        # 按照半径搜索: 搜索半径内的所有的点,按照由近到远排序
        if (!empty($lat) && !empty($lng)) {
            $point = array(doubleval($lng), doubleval($lat));
            $distance = $distance/1000;
            
            if ($distance) {
                $query['location'] = array(
                  '$geoWithin' => array(
                      '$centerSphere' => array($point, $distance/6371)
                  )  
                );
            } else {
                $query['location'] = array(
                  '$nearSphere' => $point
                );
            }
        }
        
        if ($approved) {
            $query['approved'] = (int)$approved;
		}
        
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'orby';

        $some_fields = array(
            '_id'=>1, 'name'=>1, 'location'=>1, 'address'=>1, 'summary'=>1, 'cover_id'=>1, 'phone'=>1, 'worktime'=>1, 'type'=>1, 'product_count'=>1,'view_count'=>1
        );
		
        $options['some_fields'] = $some_fields;
        
        $service = Sher_Core_Service_Estore::instance();
        $model = new Sher_Core_Model_Estore();
        
        $result = $service->get_store_list($query, $options);
        
        foreach($result['rows'] as $k => $v){
            //$result['rows'][$k]['cover'] = $model->rebuild_cover($v['cover']);
            $result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			unset($result['rows'][$k]['cover']);
        }
        
        // 过滤多余属性
        $filter_fields  = array('view_url', 'summary', 'cover_id', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
        
        //print_r($result);exit;
		return $this->api_json('请求成功', 0, $result);
    }
    
    /**
     * 获取店铺详情
     */
    public function view() {
        
        $id = $this->stash['id'];
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', true);
        }
        
        $service = Sher_Core_Service_Estore::instance();
        $result  = $service->get_store_by_id($id);
        
        $model = new Sher_Core_Model_Estore();
        $count = $model->count(array('city_id'=>$result['city_id']));
        $result['count_city'] = $count;
        
        $result['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
        // 过滤多余属性
        $filter_fields  = array('view_url', 'summary', 'cover_id', 'cover', '__extend__');
        
        for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($result[$key]);
        }
        
        //print_r($result);exit;
        return $this->api_json('请求成功', false, $result);
    }
    
    /**
     * 获取城市列表
     */
    public function get_city_list(){
        
        $type = isset($this->stash['type']) ? $this->stash['type'] : 'scene';
        $result = Sher_Core_Helper_View::$city;
        
        if($type == 'scene'){
            foreach($result as $k => $v){
                if(!$v['is_scene']){
                    unset($result[$k]);
                } 
            }
        } else if($type == 'estore'){
            foreach($result as $k => $v){
                if(!$v['is_estore']){
                    unset($result[$k]);
                } 
            }
        }
        //var_dump($result);die;
        return $this->ajax_json('请求成功', false, null, $result);
    }

  /**
   * 店铺商品关联查询
   */
  public function get_estore_product_list(){

		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        $eid = isset($this->stash['eid'])?(int)$this->stash['eid']:0;
        $pid = isset($this->stash['pid'])?(int)$this->stash['pid']:0;
        $e_city_id = isset($this->stash['e_city_id'])?$this->stash['e_city_id']:'';
        $p_stage_id = isset($this->stash['p_stage_id'])?(int)$this->stash['p_stage_id']:0;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
          'eid'=>1, 'pid'=>1, '_id'=>1, 'e_city_id'=>1, 'p_stage_id'=>1,
          'created_on'=>1, 'updated_on'=>1, 'product'=>1, 'estore'=>1,
        );

		$product_some_fields = array(
			'_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
			'cover_id', 'category_id', 'stage', 'summary', 'comment_star', 'tags', 'tags_s',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
		);
		
		// 查询条件
		if($eid){
			$query['eid'] = (int)$eid;
		}
		if($pid){
			$query['pid'] = $pid;
        }
		if($e_city_id){
			$query['e_city_id'] = $e_city_id;
		}
		if($p_stage_id){
			$query['p_stage_id'] = $p_stage_id;
        }

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 开启查询
        $service = Sher_Core_Service_REstoreProduct::instance();
        $result = $service->get_store_product_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
        $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
			}
      $data[$i]['_id'] = (string)$data[$i]['_id'];

      if($data[$i]['product']){
        $product = array();
        for($k=0;$k<count($product_some_fields);$k++){
          $product_key = $product_some_fields[$k];
          $product[$product_key] = isset($data[$i]['product'][$product_key]) ? $data[$i]['product'][$product_key] : null;
          // 封面图url
          $product['cover_url'] = isset($data[$i]['product']['cover']) ? $data[$i]['product']['cover']['thumbnails']['apc']['view_url'] : null;
        }
        $data[$i]['product'] = $product;
      }

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
  }

}
