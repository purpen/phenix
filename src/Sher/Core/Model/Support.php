<?php
/**
 * 投票记录
 * @author purpen
 */
class Sher_Core_Model_Support extends Sher_Core_Model_Base  {

    protected $collection = "support";
	
	# Ticket
	const TICKET_FAVOR = 1;
	const TICKET_OPPOSE = 2;
	
	# Event: 投票、预定
	const EVENT_VOTE = 1;
	const EVENT_PREORDER = 2;
	
    protected $schema = array(
    	'user_id' => null,
    	'target_id' => null,
      'ticket' => self::TICKET_FAVOR,
      'reason' => 0,
      'event'  => self::EVENT_VOTE,
    );
	
    protected $joins = array(
    	'user'  => array('user_id'  => 'Sher_Core_Model_User'),
		'product'  => array('target_id'  => 'Sher_Core_Model_Product'),
    );
	
    protected $required_fields = array('user_id', 'target_id', 'ticket');
    protected $int_fields = array('user_id', 'target_id', 'ticket', 'reason', 'event');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	$row['reason_text'] = $this->oppose_reason((int)$row['reason']);
    }
	
	/**
	 * 投票成功后，更新对象票数
	 */
	protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {
      $product = new Sher_Core_Model_Product();
      if ($this->data['ticket'] == self::TICKET_FAVOR){
        $product->inc_counter('vote_favor_count', 1, $this->data['target_id']);
      }else{
        $product->inc_counter('vote_oppose_count', 1, $this->data['target_id']);
      }
      //获取目标用户ID
      $user_id = $product->extend_load($this->data['target_id'])['user_id'];

      //添加动态提醒
      $timeline = new Sher_Core_Model_Timeline();
      if($this->data['ticket'] == self::TICKET_FAVOR){
        $evt = Sher_Core_Model_Timeline::EVT_VOTE_FAVOR;
      }elseif($this->data['ticket'] == self::EVENT_PREORDER){
        $evt = Sher_Core_Model_Timeline::EVT_VOTE_OPPOSE;
      }
      $arr = array(
        'user_id' => $this->data['user_id'],
        'target_id' => (string)$this->data['_id'],
        'type' => Sher_Core_Model_Timeline::TYPE_PRODUCT,
        'evt' => $evt,
        'target_user_id' => $user_id,
      );
      $ok = $timeline->create($arr);
      //给用户添加提醒
      if($ok){
        $user = new Sher_Core_Model_User();
        $user->update_counter_byinc($user_id, 'alert_count', 1);     
      }
      unset($product);
    }
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id,$ticket) {
		$product = new Sher_Core_Model_Product();
		
		if ($ticket == Sher_Core_Model_Support::TICKET_OPPOSE){
			$product->dec_counter('vote_oppose_count', (int)$id);
		} else {
			$product->dec_counter('vote_favor_count', (int)$id);
		}
		
		unset($product);
	}
	
	/**
	 * 默认反对票原因
	 */
	public function oppose_reason($id=null){
		$reasons = array(
			array('_id'=>1,'reason'=>'没有兴趣'),
			array('_id'=>2,'reason'=>'有类似的产品和创意'),
			array('_id'=>3,'reason'=>'不是一个消费产品'),
		);
		
		if (!empty($id)){
			for($i=0;$i<count($reasons);$i++){
				if ($reasons[$i]['_id'] == $id){
					return $reasons[$i];
				}
			}
		}
		
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
