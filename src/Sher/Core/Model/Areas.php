<?php
/**
 * 城市地域管理
 * @author purpen
 */
class Sher_Core_Model_Areas extends Sher_Core_Model_Base  {

    protected $collection = "areas";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		'city'   => null,
    	'parent_id' => 0,
		'child'   => 0,
		'layer'   => 1,
		'sort'   => 1,
		'status'  => 1,
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
	
}
?>