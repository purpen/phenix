<?php
/**
 * 新收货地址
 * @author tianshuai
 */
class Sher_App_Action_DeliveryAddress extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = '*';
	
	/**
	 * 默认入口
	 */
	public function execute(){
	}
	
	/**
	 * 列表--ajax
	 */
    public function ajax_load_more(){
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$is_default = isset($this->stash['is_default'])?(int)$this->stash['is_default']:0;
        
        $query = array();

        if($user_id){
            $query['user_id'] = $this->visitor->id;
        }
		
		// 状态
		$query['status'] = 1;
        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

        //限制输出字段
		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'conty'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1,
		);
        $options['some_fields'] = $some_fields;
        
		// 开启查询
        $service = Sher_Core_Service_DeliveryAddress::instance();
        $result = $service->get_address_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : '';
			}
            $data[$i]['_id'] = (string)$data[$i]['_id'];
		}

        $next_page = 'no';
        if(isset($result['next_page'])){
            if((int)$result['next_page'] > $page){
                $next_page = (int)$result['next_page'];
            }
        }
        
        $max = count($result['rows']);

        // 重建数据结果
        $data = array();
        for($i=0;$i<$max;$i++){
            $obj = $result['rows'][$i];
            foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($obj[$key]) ? $obj[$key] : null;
			}
            $data[$i]['_id'] = (string)$obj['_id'];
        } //end for

        $result['rows'] = $data;
        $result['nex_page'] = $next_page;

        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);

	}

    /**
     * 城市列表--ajax
     *
     */
    public function ajax_fetch_city(){
 		$oid = isset($this->stash['oid']) ? (int)$this->stash['oid'] : 0;
		$layer = isset($this->stash['layer']) ? (int)$this->stash['layer'] : 1;
		$pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;

        $china_city_model = new Sher_Core_Model_ChinaCity();

        $result = $china_city_model->fetch_city($pid, $layer);

        return $this->ajax_json('success', false, '', $result);
    }
	

}

