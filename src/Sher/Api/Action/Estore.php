<?php
/**
 * 线下店铺接口
 * @author purpen
 */
class Sher_Api_Action_Estore extends Sher_Api_Action_Base {
    
	public $stash = array(
		'id'   => 0,
        'page' => 1,
        'size' => 10,
        // 默认通过审核的
        'approved' => 2,
        // 地理位置参数
        'dis'  => 0,
        'lng'  => 0,
        'lat'  => 0,
	);
	
	protected $exclude_method_list = array('execute','get_store_list','find_stores','get_single_store','get_city_list');
    protected $filter_user_method_list = array('execute','get_store_list','find_stores','get_single_store','get_city_list');

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
		
        // approved=2&channel=appstore&client_id=1415289600&dis=1000&lat=39.982965&lng=116.492537&page=0&sign=0f165a24ac11eea127adc2ae39ec7829&size=10&time=1458813768.644313&uuid=13B0EB10-A564-4B1E-AC79-B3E987DFADBC
        
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 10;
        
        $approved = $this->stash['approved']; // 是否审核
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
            $result['rows'][$k]['cover'] = $model->rebuild_cover($v['cover']);
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
    public function get_single_store() {
        
        $id = $this->stash['id'];
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', true);
        }
        
        $service = Sher_Core_Service_Estore::instance();
        $result  = $service->get_store_by_id($id);
        
        // 过滤多余属性
        $filter_fields  = array('view_url', 'summary', 'cover_id', 'cover', '__extend__');
        
        $cover = array(
            'mini_view_url' => $result['cover']['thumbnails']['mini']['view_url'],
            'tiny_view_url' => $result['cover']['thumbnails']['tiny']['view_url'],
            'small_view_url' => $result['cover']['thumbnails']['small']['view_url'],
            'medium_view_url' => $result['cover']['thumbnails']['medium']['view_url'],
            'large_view_url' => $result['cover']['thumbnails']['large']['view_url'],
            'big_view_url' => $result['cover']['thumbnails']['big']['view_url'],
            'huge_view_url' => $result['cover']['thumbnails']['huge']['view_url'],
            'massive_view_url' => $result['cover']['thumbnails']['massive']['view_url'],
            'resp_view_url' => $result['cover']['thumbnails']['resp']['view_url'],
            'hd_view_url' => $result['cover']['thumbnails']['hd']['view_url'],
            'md_view_url' => $result['cover']['thumbnails']['md']['view_url'],
            'hm_view_url' => $result['cover']['thumbnails']['hm']['view_url'],
            'ava_view_url' => $result['cover']['thumbnails']['ava']['view_url'], 
        );
        
        for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($result[$key]);
        }
        
        $result['cover'] = $cover;
        
        //print_r($result);exit;
        return $this->api_json('请求成功', false, $result);
    }
    
    /**
     * 获取城市列表
     */
    public function get_city_list(){
        $result = Sher_Core_Model_Estore::$city;
        //var_dump($result);die;
        return $this->ajax_json('请求成功', false, null, $result);
    }
}