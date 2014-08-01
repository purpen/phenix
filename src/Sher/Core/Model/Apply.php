<?php
/**
 * 活动申请表格
 * @author purpen
 */
class Sher_Core_Model_Apply extends Sher_Core_Model_Base  {

    protected $collection = "applitable";
	
	# 默认值
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
		
        # 申请信息
		'name' => '',
		'phone' => '',
		'province' => '',
		'district' => '',
		'address' => '',
		
    	'result' => self::RESULT_REJECT,
		'type' => self::TYPE_TRY,
		
		'state'  => 0,
    );
	
    protected $joins = array(
    	'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
	
    protected $required_fields = array('user_id', 'target_id', 'content');
	
    protected $int_fields = array('user_id', 'target_id', 'state', 'result');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
	/**
	 * 验证信息
	 */
    protected function validate() {
		// 新建记录
		if($this->insert_mode){
			if (!$this->check_reapply()){
				throw new Sher_Core_Model_Exception('该用户已申请过！');
			}
		}
		
        return true;
    }
	
	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，关联事件
	 */
    protected function after_save() {
		$target_id = $this->data['target_id'];
		$type = $this->data['type'];
		// 更新申请人数
		if($type == self::TYPE_TRY){
			$try = new Sher_Core_Model_Try();
			$try->increase_counter('apply_count', 1, (int)$target_id);
		}
    }
	
	/**
	 * 验证是否已申请
	 */
	public function check_reapply($user_id=null, $target_id=null, $type=self::TYPE_TRY){
		if(is_null($user_id)){
			$user_id = $this->user_id;
		}
		if(is_null($target_id)){
			$target_id = $this->target_id;
		}
		
		$row = $this->first(array('user_id' => (int)$user_id, 'target_id' => (int)$target_id, 'type' => $type));
		if(!empty($row)){
			return false;
		}
		return true;
	}
	
	/**
	 * 设置通过或驳回审核
	 */
	public function mark_set_result($id, $result){
		return $this->update_set($id, array('result'=>(int)$result));
	}
	
	
}
?>