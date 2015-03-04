<?php
/**
 * 红包管理
 * @author purpen
 */
class Sher_Core_Model_Bonus extends Sher_Core_Model_Base {
	
    protected $collection = "bonus";
	
	# 红包状态
	const STATUS_DISABLED = 0;
	const STATUS_PENDING = 1;
	const STATUS_OK = 2;
	const STATUS_LOCK = 3;
	const STATUS_GOT = 4;
	
	# 使用状态
	const USED_DEFAULT = 1;
	const USED_OK = 2;
	
	/**
	 * 记录活动代号，便于统计
	 */
	public $names = array(
		'T9', # 上线红包
		'TG', # 玩蛋去活动
    'VA', #情人节红包20
    'RE', #注册送
    'IV', #邀请送
	);
	
    protected $schema = array(
		# 红包码
        'code' => '',
		# 红包金额
		'amount' => 0,
		
		# 所属人
        'user_id' => 0,
		# 领取时间
		'get_at'  => 0,
		
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
		
		# 活动代号
		'xname' => 'T9',

    #限制最低使用金额
    'min_amount' => 0,
		
		# 状态
		'status' => self::STATUS_OK,
    );
	
    protected $required_fields = array('code','amount');
    protected $int_fields = array('user_id','get_at','used_by','used_at','used','expired_at');
	
    protected $joins = array();
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
		if ($row['used'] != 2) {
			if ($row['expired_at'] < time()){
				$row['is_expired'] = true;
				$row['expired_label'] = '已过期';
			} else {
				$row['expired_label'] = sprintf('将于 %s 过期', date('Y-m-d H:i:s', $row['expired_at']));
			}
		}
	}
	
	/**
	 * 获取一个红包，同时锁定红包
	 */
	public function pop($xname=0){
    if($xname){
      $query = array(
        'used' => self::USED_DEFAULT,
        'status' => self::STATUS_OK,
        'xname' => $xname,
      );    
    }else{
      $query = array(
        'used' => self::USED_DEFAULT,
        'status' => self::STATUS_OK,
      );  
    }

		$updated = array(
			'$set' => array(
				'status' => self::STATUS_PENDING,
			),
		);
		
		$options = array(
			'query'  => $query,
			'update' => $updated,
		);
		
		return self::$_db->find_and_modify($this->collection, $options);
	}
	
	/**
	 * 通过红包码查询
	 */
	public function find_by_code($code){
		return $this->first(array('code' => $code));
	}
	
	/**
	 * 赠送给某人
	 */
	public function give_user($code, $user_id, $expired_time=0){
		$crt = array('code' => $code);
    if($expired_time){
      $expired_at = (int)$expired_time;
    }else{
      $expired_at = time() + 30*24*60*60;
    }
		return $this->update_set($crt, array(
			'user_id' => (int)$user_id,
			'get_at'  => time(),
			'expired_at' => $expired_at,
			# 标识所属状态
			'status' => self::STATUS_GOT,
		));
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
	 * 获取随机概率
	 * 概率设定：5元红包 50%，10元红包 45%，20元红包 4%，50元红包 0.8%，100元红包 0.2%；
	 *         5元红包 50%，10元红包 45%，20元红包 5% 
	 */
	protected function _get_random_chance(){ 
	    $list = array(
			//'A' => 2,
			//'B' => 8, 
			'C' => 50, 
			'D' => 450, 
			'E' => 500
		);
	    $sum = 0;
		
		# 这个数组记录了每个切割点的值，就是记录了数轴上，2,10,50,500,1000的值
	    $listPoint = array(0);
	    foreach($list as $key => $value){
			# 计算出权值的总和
	        $sum += $value;
			# 把分割点放到数组中
	        array_push($listPoint, $sum);
	    }
		
		# 取0到sum之间一个随机值
	    $num = rand(0, $sum);
	    for($i=0; $i<count($listPoint)-1; $i++){
			# 判断随机值落在哪个范围内  
	        if($num >= $listPoint[$i] && $num <= $listPoint[$i + 1]){ 
	            $elem = array_slice($list, $i, 1); 
				# 第i项的值
	            return key($elem);
	        } 
	    }
	}
	
	/**
	 * 批量生成红包
	 * @var $count 默认生成红包数量
	 */
	public function create_batch_bonus($count=100, $xname='T9', $char=0){
		# 红包金额
	    $bonus = array(
			//'A' => 100,
			//'B' => 50, 
			'C' => 20, 
			'D' => 10, 
			'E' => 5
		);
		
      for($i=0; $i<$count; $i++){
        //生成指定金额
        if($char){
          $amount = $bonus[$char];       
        }else{
          $char = $this->_get_random_chance();
          $amount = $bonus[$char];       
        }

			$code = self::rand_number_str(8);
			
			try{
				// 生成新红包
				$this->create(array(
					'code'   => $code,
					'amount' => $amount,
					'xname'  => $xname,
				));
			}catch(Sher_Core_Model_Exception $e){
				Doggy_Log_Helper::error('Failed to create bonus:'.$e->getMessage());
			}
		}
	}

	/**
	 * 批量生成指定限额红包
	 * @var $count 默认生成红包数量
	 */
	public function create_specify_bonus($count=5, $xname='RE', $char='A', $min_char='A'){
		# 红包金额
	  $bonus = array(
			'A' => 50,
			'B' => 100, 
    );

    #最低限额
    $min_amounts = array(
      'A' =>  99,
      'B' =>  199,
    );
		
    for($i=0; $i<$count; $i++){
      //生成指定金额
      $amount = $bonus[$char]; 
      $min_amount = $min_amounts[$min_char];
			$code = self::rand_number_str(8);
			
			try{
				// 生成新红包
				$this->create(array(
					'code'   => $code,
					'amount' => $amount,
          'xname'  => $xname,
          'min_amount'  => $min_amount,
				));
			}catch(Sher_Core_Model_Exception $e){
				Doggy_Log_Helper::error('Failed to create bonus:'.$e->getMessage());
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
