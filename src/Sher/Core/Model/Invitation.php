<?php
/**
 * Invitation code
 */
class Sher_Core_Model_Invitation extends Sher_Core_Model_Base  {
    protected $collection = "invitation";
    protected $schema = array(
        'used' => 0,
        'user_id'=> 0,
    	'used_at' => 0,
    	'used_by'  => 0,
    );
    protected $required_fields = array('user_id');
    protected $int_fields = array('used','user_id','used_at','used_by');
    protected $joins = array(
        'user' =>   array('user_id' => 'Sher_Core_Model_User'),
        'invited_user' => array('used_by' => 'Sher_Core_Model_User'),
	);

    public function mark_used($code,$used_by) {
        return $this->update_set($code,array('used_by' => (int)$used_by,'used_at' => time(),'used' => 1));
    }

    public function generate_for_user($user_id,$amount) {
        $result = array();
        for ($i=0; $i < $amount; $i++) {
            $this->create(array('user_id' => (int)$user_id));
            $result[$i] = (string)$this->id;
        }
        return $result;
    }
    
    /**
     * 验证是否服务获得邀请码条件
     * 1、是否还有未使用的邀请码
     * 
     * @return array
     */
    public function give_check_condition($user_id,$amount=5){
		$query = array(
			'user_id' => (int)$user_id,
			'used' => 0,
		);
    	$cnt = $this->count($query);
    	if($cnt == 0){
    		return $this->generate_for_user($user_id, $amount);
    	}
    }
}
?>