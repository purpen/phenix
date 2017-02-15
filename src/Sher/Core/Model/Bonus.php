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
        'AD', # 后台自定义发送
		'T9', # 上线红包
		'TG', # 玩蛋去活动
        'VA', # 情人节红包20
        'RE', # 注册送
        'IV', # 邀请送 50/100
        'D1', # 线下活动,注册抽奖(ces,大赛)
        'ZP', # 招聘H5分享
        'QX', # 七夕注册送红包100 满299可用
        'JBL', # 七夕敦请送JBL指定红包
        'SQR', # 扫码送30元红包
        'DB', # 兑吧送红包
        'SD', # 签到抽奖红包
        'DA', # 首次下载APP
        'AS', # app下单分享送5元
        'APS', # app下载兑吧送红包
        'SB' , # 通过链接送红包活动
        'FIU_N1', # 内测送200元红包
        'DA100', # 内测 满999减100
        'DA50', #内测 满499减50
        'DA30', # 内测 满299减30
        'DA20', #内测 满199减20
        'LSD99', # 螺丝刀99
        'FIU_NEW30', # Fiu店新用户送30
        'FIU_DROW', # Fiu店抽奖
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
        # 限制使用产品
        'product_id' =>  0,
		
		# 使用在某订单
		'order_rid' => '',
		
		# 过期时间
		'expired_at' => 0,
		
		# 活动代号
		'xname' => 'T9',

        # 限制最低使用金额
        'min_amount' => 0,

        # 所属活动ID(指定红包使用范围)
        'bonus_active_id' => '',
		
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
        $row['is_expried'] = false;
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
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){
            // 更新红包活动数量
            if(!empty($this->data['bonus_active_id'])){
                $bonus_active_model = new Sher_Core_Model_BonusActive();
                $bonus_active_model->inc_counter('item_count', 1, $this->data['bonus_active_id']);
                unset($bonus_active_model);
            }

        }
    }

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        if(isset($options['bonus_active_id']) && !empty($options['bonus_active_id'])){
            $bonus_active_model = new Sher_Core_Model_BonusActive();
            $bonus_active_model->dec_counter('item_count', $options['bonus_active_id']);
            unset($bonus_active_model);       
        }
		
		return true;
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
	public function give_user($code, $user_id, $day=0){
		$crt = array('code' => $code);
    if($day){
      $expired_at = time() + 60*60*24*(int)$day;
    }else{
      $expired_at = time() + 30*24*60*60;
    }
		$ok = $this->update_set($crt, array(
			'user_id' => (int)$user_id,
			'get_at'  => time(),
			'expired_at' => $expired_at,
			# 标识所属状态
			'status' => self::STATUS_GOT,
        ));

        // 添加提醒数量
        if($ok){
            // 给用户添加提醒
            $user_model = new Sher_Core_Model_User();
            $user_model->update_counter_byinc($user_id, 'fiu_bonus_count', 1); 
        }
        return $ok;
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
	public function create_batch_bonus($count=10, $xname='T9', $char=0){
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
	public function create_specify_bonus($count=1, $xname='RE', $char='A', $min_char='A', $product_id=0, $bonus_active_id=''){
		# 红包金额
	  $bonus = array(
          'A' => 50,
          'B' => 100,
          'C' => 30, 
          'D' => 52,
          'E' => 5,
          'F' => 9.9,
          'G' => 10,
          'H' => 200,
          'I' => 20,
    );

    #最低限额
    $min_amounts = array(
      'A' =>  99,
      'B' =>  199,
      'C' =>  0,
      'D' => 299,
      'E' => 399,
      'F' => 50,
      'G' => 999,
      'H' => 499,
      'I' => 30,
      'J' => 100,
      'K' => 10,
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
          'product_id' => (int)$product_id,
          'bonus_active_id' => $bonus_active_id,
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

    /**
     * 返回活动码
     */
    public function x_name(){
        return $this->names;
    }
	
}

