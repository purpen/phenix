<?php
/**
 * 礼品卡管理
 * @author purpen
 */
class Sher_Core_Model_Gift extends Sher_Core_Model_Base {
	
    protected $collection = "giftcard";
	
	# 红包状态
	const STATUS_DISABLED = 0;
	const STATUS_PENDING = 1;
	const STATUS_OK = 2;
	const STATUS_LOCK = 3;
	const STATUS_GOT = 4;
	
	# 使用状态
	const USED_DEFAULT = 1;
	const USED_OK = 2;
	
    protected $schema = array(
		# 礼品码
        'code' => '',
		# 抵扣金额
		'amount' => 0,

    #最低限额
    'min_cost' => 0,
		
		# 使用产品
		'product_id' => 0,
		
		# 生成者
        'user_id' => 0,
		# 使用者
    	'used_by' => 0,
		
		# 使用时间
		'used_at' => 0,
		# 是否使用
		'used' => self::USED_DEFAULT,
		
		# 使用在某订单
		'order_rid' => '',
		
		# 过期时间
		'expired_at' => 0,
		
		# 状态
		'status' => self::STATUS_OK,
    );
	
    protected $required_fields = array('code','amount');
    protected $int_fields = array('product_id','user_id','used_by','used_at','used','expired_at');
		protected $float_fields = array('amount', 'min_cost');
	
    protected $joins = array();
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
		if ($row['used'] != self::USED_OK) {
			if ($row['expired_at'] < time()){
				$row['is_expired'] = true;
				$row['expired_label'] = '已过期';
			} else {
				$row['expired_label'] = sprintf('将于 %s 过期', date('Y-m-d H:i:s', $row['expired_at']));
			}
		}
	}
	
	/**
	 * 通过码查询
	 */
	public function find_by_code($code){
		return $this->first(array('code' => $code));
	}
	
	/**
	 * 设置使用
	 */
    public function mark_used($code, $used_by, $order_rid) {
		$crt = array(
			'code' => $code,
		);
		
        return $this->update_set($crt, array(
			'used_by' => (int)$used_by,
			'used_at' => time(),
			'used' => self::USED_OK,
			'order_rid' => $order_rid,
		));
    }
	
	/**
	 * 解冻
	 */
	public function unpending($id){
		return $this->update_set($id, array('status'=>self::STATUS_OK));
	}
	
	/**
	 * 锁定
	 */
	public function locked($id){
		return $this->update_set($id, array('status'=>self::STATUS_LOCK));
	}
	
	/**
	 * 批量生成码
	 * @var $count 默认生成数量
	 */
	public function create_batch_gift($product_id, $amount, $min_cost, $user_id, $count=100, $prefix='G', $expired_days=7){
		for($i=0; $i<$count; $i++){
			$code = self::rand_number_str(10);
			
			try{
				// 生成新码
				$this->create(array(
					'code'   => $prefix.$code,
					'product_id' => (int)$product_id,
          'amount' => (float)$amount,
          'min_cost' => (float)$min_cost,
					'user_id' => (int)$user_id,
					'expired_at' => time() + $expired_days*24*60*60,
				));
			}catch(Sher_Core_Model_Exception $e){
				Doggy_Log_Helper::error('Failed to create gift:'.$e->getMessage());
			}
		}
	}
    
    /**
     * 产生一个特定长度的字符串
     * 
     * @param int $len
     * @param string $chars
     * @return string
     */
    public static function rand_number_str($len){
        $string = '';
		$chars = array_merge(range('a','z'),range(0, 9),range('A','Z'));
		$chars = implode($chars, '');
        for($i=0; $i<$len; $i++){
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
    }
	
}
?>
