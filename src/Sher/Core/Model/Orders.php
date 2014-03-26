<?php
/**
 * 订单列表
 * @author purpen
 */
class Sher_Core_Model_Orders extends Sher_Core_Model_Base {

    protected $collection = "orders";
	
    protected $schema = array(
	    'user_id' => null,
    );

	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id');

	protected $joins = array(
	    'user' =>   array('user_id' => 'Sher_Core_Model_User')
	);

}
?>