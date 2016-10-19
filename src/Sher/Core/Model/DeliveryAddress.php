<?php
/**
 * 用户收获地址(new)
 * @author tianshuai
 */
class Sher_Core_Model_DeliveryAddress extends Sher_Core_Model_Base  {

    protected $collection = "delivery_address";
	
    protected $schema = array(
    	'user_id' => 0,
		'name'  => null,
		'phone' => null,
		'province_id' => 0,
		'city_id'  => 0,
        'county_id' => 0,
        'town_id' => 0,
		'address' => '',
		'zip'     => '',
		'email'   => '',
		'is_default' => 0,
    );
	
    protected $joins = array(

    );
	
    protected $required_fields = array('user_id', 'phone', 'address');
	
    protected $int_fields = array('user_id', 'province_id', 'city_id', 'county_id', 'town_id', 'is_default');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
        $row['province'] = $row['city'] = $row['county'] = $row['town'] = '';

        if(!empty($row['province_id'])) $row['province'] = $this->fetch_name($row['province_id'], 1);
        if(!empty($row['city_id'])) $row['city'] = $this->fetch_name($row['city_id'], 2);
        if(!empty($row['county_id'])) $row['county'] = $this->fetch_name($row['county_id'], 3);
        if(!empty($row['town_id'])) $row['town'] = $this->fetch_name($row['town_id'], 4);
    }

    /**
     * 获取地区名称
     */
    protected function fetch_name($oid, $layer){
        $china_city_model = new Sher_Core_Model_ChinaCity();
        if(empty($oid)) return '';
        $china_city = $china_city_model->first(array('oid'=>(int)$oid, 'layer'=>(int)$layer));
        if(empty($china_city)) return '';
        return $china_city['name'];
    }
	
}

