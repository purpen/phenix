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
        // 如果是新的记录
        if($this->insert_mode) {
            $product = new Sher_Core_Model_Product();
            if($this->data['ticket'] == self::TICKET_FAVOR){
                $product->inc_counter('vote_favor_count', 1, $this->data['target_id']);
            }else{
                $product->inc_counter('vote_oppose_count', 1, $this->data['target_id']);
            }
            
            // 增加积分
            $service = Sher_Core_Service_Point::instance();
            // 投票他人创意
            $service->send_event('evt_vote_idea', $this->data['user_id']);
            
            // 添加动态提醒
            $timeline = Sher_Core_Service_Timeline::instance();
            $timeline->broad_target_vote($this->data['user_id'], (int)$this->data['target_id'], Sher_Core_Util_Constant::TYPE_PRODUCT);
            
            // 如果是投票,添加提醒
            if($this->data['event'] == self::EVENT_VOTE){
                if($this->data['ticket'] == self::TICKET_FAVOR){
                    $evt = Sher_Core_Model_Remind::EVT_VOTE_FAVOR;
                    $reason = null;     
                }else{
                    $evt = Sher_Core_Model_Remind::EVT_VOTE_OPPOSE;
                    $reason_hash = $this->oppose_reason($this->data['reason']);
                    $reason = $reason_hash['reason'];
                }
                // 获取目标用户ID
                $data = $product->extend_load($this->data['target_id']);
                $user_id = $data['user_id'];
                
                // 创意被他人投票
                $service->send_event('evt_by_vote_idea', $user_id);

                // 添加提醒
                /*
                $remind = new Sher_Core_Model_Remind();
                $arr = array(
                    'user_id'=> $user_id,
                    's_user_id'=> $this->data['user_id'],
                    'evt'=> $evt,
                    'content'=> $reason,
                    'kind'=> Sher_Core_Model_Remind::KIND_PRODUCT,
                    'related_id'=> (int)$this->data['target_id'],
                    'parent_related_id'=> (string)$this->data['_id'],
                );
                $ok = $remind->apply_and_save($arr);
                if($ok){
                    $user = new Sher_Core_Model_User();
                    $user->update_counter_byinc($user_id, 'alert_count', 1);     
                }*/
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
