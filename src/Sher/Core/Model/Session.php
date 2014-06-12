<?php
/**
 * Session.
 * @author purpen
 */
class Sher_Core_Model_Session extends Sher_Core_Model_Base {
    protected $collection = 'session';
	
    protected $schema = array(
        'user_id' => null,
        'is_login' => 0,
		'alive' => 0,
		'serial_no' => 0,
    );
	
    protected $int_fields = array('user_id', 'is_login', 'alive', 'serial_no');
	
}
?>