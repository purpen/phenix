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
	
	protected $exclude_method_list = array('execute','get_store_list','find_stores','get_single_store');
    //protected $filter_user_method_list = array('execute','get_store_list','find_stores','get_single_store');

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
        
        $approved = $this->stash['approved']; // 是否审核
        // 基于地理位置的查询，从城市内查询
        $distance = $this->stash['dis']; // 距离、半径
        $lng = $this->stash['lng']; // 经度
        $lat = $this->stash['lat']; // 纬度
        
        // 判断查询条件
		$query   = array();
		$options = array();
        
		if ($approved) {
            $query['approved'] = (int)$approved;
		}
        
        # 按照半径搜索: 搜索半径内的所有的点,按照由近到远排序
        if (!empty($lat) && !empty($lng)) {
            $point = array($lng, $lat);
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
		
        $service = Sher_Core_Service_Estore::instance();
        
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'orby';

        $some_fields = array(
            '_id'=>1, 'name'=>1, 'location'=>1, 'address'=>1, 'summary'=>1, 'cover_id'=>1, 'phone'=>1, 'worktime'=>1, 'type'=>1, 'product_count'=>1,'view_count'=>1
        );
		
        $options['some_fields'] = $some_fields;
        $result = $service->get_store_list($query, $options);
        
        $model = new Sher_Core_Model_Estore();
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
            'mini' => $result['cover']['thumbnails']['mini'],
            'tiny' => $result['cover']['thumbnails']['tiny'],
            'small' => $result['cover']['thumbnails']['small'],
            'medium' => $result['cover']['thumbnails']['medium'],
            'large' => $result['cover']['thumbnails']['large'],
            'big' => $result['cover']['thumbnails']['big'],
            'huge' => $result['cover']['thumbnails']['huge'],
            'massive' => $result['cover']['thumbnails']['massive'],
            'resp' => $result['cover']['thumbnails']['resp'],
            'hd' => $result['cover']['thumbnails']['hd'],
            'md' => $result['cover']['thumbnails']['md'],
            'hm' => $result['cover']['thumbnails']['hm'],
            'ava' => $result['cover']['thumbnails']['ava'], 
        );
        
        for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($result[$key]);
        }
        
        $result['cover'] = $cover;
        
        //print_r($result);exit;
        return $this->api_json('请求成功', false, $result);
    }
    
}