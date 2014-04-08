<?php
/**
 * 投票记录
 * @author purpen
 */
class Sher_Core_Model_Vote extends Sher_Core_Model_Base  {

    protected $collection = "vote";
	
	# Ticket
	const TICKET_FAVOR = 1;
	const TICKET_OPPOSE = 2;
	
    protected $schema = array(
    	'user_id' => null,
    	'target_id' => null,
		'ticket' => self::TICKET_FAVOR,
		'reason' => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'target_id', 'ticket');
    protected $int_fields = array('user_id', 'target_id', 'ticket', 'reason');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {}
	
	/**
	 * 投票成功后，更新对象票数
	 */
	protected function after_save() {
		$product = new Sher_Core_Model_Product();
		if ($this->data['ticket'] == self::TICKET_FAVOR){
			$product->inc_counter('vote_favor_count', 1, $this->data['target_id']);
		}else{
			$product->inc_counter('vote_oppose_count', 1, $this->data['target_id']);
		}
		unset($product);
	}
	
	/**
	 * 默认反对票原因
	 */
	public function oppose_reason(){
		$reasons = array(
			array('_id'=>1,'reason'=>'没有兴趣'),
			array('_id'=>2,'reason'=>'有类似的产品和创意'),
			array('_id'=>3,'reason'=>'不是一个消费产品'),
		);
		
		return $reasons;
	}
	
    /**
     * 检测是否投票
     */
    public function check_voted($user_id, $target_id){
        $query['target_id'] = (int) $target_id;
		$query['user_id'] = (int) $user_id;
		
        $result = $this->count($query);
		
        return $result>0?true:false;
    }
	
}
?>