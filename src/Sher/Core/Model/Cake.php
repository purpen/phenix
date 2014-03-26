<?php
/**
 * 公告
 * @author purpen
 */
class Sher_Core_Model_Cake extends Sher_Core_Model_Base {    
    protected $collection = "cake";

    protected $schema = array(
		'user_id' => null,
		'content' => null,
		'random' => null,
		'state' => 0,
    );
	
    protected $int_fields = array('state');

	protected $required_fields = array('content');
	
    protected $joins = array();

	protected function before_save(&$data) {
		// 添加随机数
		srand((double)microtime()*1000000);
		$data['random'] = rand(0,99999999);
    }
	
}
?>