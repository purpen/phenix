<?php
/**
 * 活动申请表格
 * @author purpen
 */
class Sher_Core_Model_Apply extends Sher_Core_Model_Base  {

    protected $collection = "apply";
	
	# 拒绝
	const RESULT_REJECT = 0;
	# 通过
	const RESULT_PASS = 1;
	
	# 申请类型
	const TYPE_TRY = 1;
	
    protected $schema = array(
		# 所属对象
		'target_id' => 0,
        'user_id' => 0,
    	'content' => '',
        
    	'result' => self::RESULT_REJECT,
		'type' => self::TYPE_TRY,
		
		'state'  => 0,
    );
	
    protected $joins = array(
    	'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
	
    protected $required_fields = array('user_id', 'content');
	
    protected $int_fields = array('user_id', 'state', 'result');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
?>