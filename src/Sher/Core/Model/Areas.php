<?php
/**
 * 城市地域管理
 * @author purpen
 */
class Sher_Core_Model_Areas extends Sher_Core_Model_Base  {

    protected $collection = "areas";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
      '_id' => null,
		'city'   => null,
    	'parent_id' => 0,
		'child'   => 0,
		'layer'   => 1,
		'sort'   => 1,
		'status'  => 1,
    );
	
    /**
     * 重点城市地域
     */
    protected $cities = array(
        array(
            'id' => 1,
            'name' => '北京',
            'pat'  => '/北京/i',
        ),
        array(
            'id' => 2,
            'name' => '深圳',
            'pat'  => '/深圳/i',
        ),
        array(
            'id' => 3,
            'name' => '广州',
            'pat'  => '/广州/i',
        ),
        array(
            'id' => 4,
            'name' => '上海',
            'pat'  => '/上海/i',
        ),
        array(
            'id' => 5,
            'name' => '杭州',
            'pat'  => '/杭州/i',
        ),
        array(
            'id' => 6,
            'name' => '南京',
            'pat'  => '/南京/i',
        ),
        array(
            'id' => 7,
            'name' => '苏州',
            'pat'  => '/苏州/i',
        ),
        array(
            'id' => 8,
            'name' => '武汉',
            'pat'  => '/武汉/i',
        ),
        array(
            'id' => 9,
            'name' => '天津',
            'pat'  => '/天津/i',
        ),
        
        array(
            'id' => 200,
            'name' => '其他',
            'pat'  => '/其他/i',
        ),
    );
    
    protected $joins = array();
    
    protected $required_fields = array('city');
    protected $int_fields = array('parent_id', 'child', 'layer', 'sort', 'status');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
	/**
	 * 获取所有一级省市列表
	 */
	public function fetch_provinces(){
		$query['parent_id'] = 0;
		$options['sort'] = array('sort' => 1);
		
		return $this->find($query, $options);
	}
	
	/**
	 * 获取所有二级地区列表
	 */
	public function fetch_districts($fid=0){
		$query['parent_id'] = (int)$fid;
		$options['sort'] = array('sort' => 1);
		
		return $this->find($query, $options);
	}
    
	/**
	 * 获取全部城市或某个
	 */
	public function find_cities($id=0){
		if($id){
			for($i=0;$i<count($this->cities);$i++){
				if ($this->cities[$i]['id'] == $id){
					return $this->cities[$i];
				}
			}
			return array();
		}
		return $this->cities;
	}
    
    /**
     * 根据城市名称，查询ID
     */
    public function match_city($city_name){
        $city_name = Sher_Core_Helper_Util::trimall($city_name);
		for($i=0;$i<count($this->cities);$i++){
			if(preg_match_all($this->cities[$i]['pat'], $city_name, $matches)){
				return $this->cities[$i]['id'];
			}
		}
        return false;
    }
    
}

