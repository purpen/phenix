<?php
/**
 * 实验室-付费会员订单
 * @author tianshuai
 */
class Sher_Core_Model_DOrder extends Sher_Core_Model_Base  {

    protected $collection = "d_order";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;

  const KIND_D3IN = 1;
  const KIND_VIP = 2;
  const KIND_OTHER = 3;
	
  protected $schema = array(
    'rid' => 0,
    'user_id' => 0,

    ## 产品ID
    'item_id' => null,
    ## 产品名称
    'item_name' => '',

    'pay_money'   => 0,
    'total_money' => 0,
    # 优惠类型
    'discount_type' => 0,

		## 支付方式
    'payment_method' => 0,
		## 第三方交易号
		'trade_no' => 0,
		'trade_site' => Sher_Core_Util_Constant::TRADE_ALIPAY,

		# 取消订单标识及时间
		'is_canceled' => 0,
		'canceled_date' => 0,

    #退款成功标识及时间
    'is_refunded' => 0,
    'refunded_price'  =>  0,
    'refunded_date' => 0,

		## 时间（完成）
    'is_finished' => 0,
		'finished_date' => 0,
		# 关闭时间
    'is_closed' => 0,
		'closed_date' => 0,

    #申请退款标识及时间
    'is_refunding' => 0,
    'refunding_date' => 0,
    'refund_reason'  =>  null,

    #退款成功标识及时间
    'is_refunded' => 0,
    'refunded_price'  =>  null,
    'refunded_date' => 0,
		
		 ## 备注
    'summary' => '',

    ## 是否删除
    'deleted' => 0,

    # 订单类型 1,实验室预约; 2,会员付费; 3, 其它
    'kind' => self::KIND_D3IN,

    # 状态
    'state' => Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,

		# 过期时间,(普通订单、预售订单)
		'expired_time' => 0,

		## 安全信息
		'ip' => '',

		# 来源站点
		'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,

  );

	protected $joins = array(
	  'user' => array('user_id' => 'Sher_Core_Model_User'),
	);

  protected $required_fields = array('user_id', 'item_id', 'item_name');
  protected $int_fields = array('rid', 'user_id', 'kind', 'state', 'expired_time', 'from_site', 'finished_date', 'discount_type', 'deleted');
  protected $float_fields = array('pay_money', 'total_money', 'refunded_price');
	
	
	/**
	 * 保存之前事件
	 */
	protected function before_save(&$data) {
		$this->validate_order($data);

		// 新建数据,补全默认值
		if ($this->is_saved()){
			$data['rid'] = $this->gen_order_id($data['_id'], '2');
		}
		
		// 设置过期时间，过期后自动关闭
		$data['expired_time'] = time() + Sher_Core_Util_Constant::D3IN_EXPIRE_TIME;
		
	    parent::before_save($data);
	}

	
	/**
	 * 生成订单编号, 十位数字符
	 */
	protected function gen_order_id($id, $prefix='2'){
		
		$rid  = $prefix;
		$len = strlen((string)$id);
		if ($len <= 5) {
			$rid .= date('ymd');
			$rid .= sprintf("%05d", $id);
		}else{
			$rid .= substr(date('md'), 0, 11 - $len);
			$rid .= $id; 
		}
		
		return $rid;
	}
	
	/**
	 * 扩展关联数据
	 */
  protected function extra_extend_model_row(&$row) {
    $row['payment'] = $this->find_payment_methods($row['payment_method']);
		$row['view_url'] = Sher_Core_Helper_Url::d_order_view_url($row['rid']);
		$row['wap_view_url'] = Sher_Core_Helper_Url::d_order_wap_view_url($row['rid']);
		$row['state_label'] = $this->get_order_status_label($row['state']);
    $row['from_site_label'] = $this->get_from_label($row['from_site']);
  }
	
	/**
	 * 通过rid查找
	 */
	public function find_by_rid($rid){
		$row = $this->first(array('rid'=>$rid));
        if (!empty($row)) {
            $row = $this->extended_model_row($row);
        }
		
		return $row;
	}

  /**
   * 付款方式
   * array(
 *		'id' => 'w',
   *      'name' => '微信支付',
   *      'summary' => '微信支付快捷方便'
   * )
   * @var array
   */
  private $payment_methods = array(
    array(
    'id' => 'a',
          'name' => '在线支付',
    'active' => 'active',
          'summary' => '支付宝作为诚信中立的第三方机构，充分保障货款安全及买卖双方利益,支持各大银行网上支付。'
        ),
    
  );

	/**
	 * 过滤items
	 */
	protected function validate_order(&$data){

	}

	/**
	 * 订单状态标签
	 */
	protected function get_order_status_label($status){
		switch($status){
			case Sher_Core_Util_Constant::ORDER_EXPIRED:
				$status_label = '已过期';
				break;
			case Sher_Core_Util_Constant::ORDER_CANCELED:
				$status_label = '已取消';
				break;
			case Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT:
				$status_label = '等待付款';
				break;
			case Sher_Core_Util_Constant::ORDER_READY_REFUND:
				$status_label = '退款中';
				break;
			case Sher_Core_Util_Constant::ORDER_REFUND_DONE:
				$status_label = '已退款';
				break;
			case Sher_Core_Util_Constant::ORDER_PUBLISHED:
				$status_label = '已完成';
				break;
		}
		
		return $status_label;
	}

  /**
   * 返回对应的付款方式
   * 
   * @return mixed
   */
  public function find_payment_methods($key=null){
      if(is_null($key)){
          return $this->payment_methods;
      }
  
  for($i=0;$i<count($this->payment_methods);$i++){
    if ($this->payment_methods[$i]['id'] == $key){
      return $this->payment_methods[$i];
    }
  }
  
  return null;
  }

	/**
	 * 关闭订单
	 */
	public function close_order($id, $options=Array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_CANCELED, $options);
	}

	/**
	 * 过期订单
	 */
	public function expired_order($id, $options=Array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_EXPIRED, $options);
	}

	/**
	 * 支付成功
	 */
	public function success_order($id, $options=Array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_PUBLISHED, $options);
	}

	/**
	 * 处理订单
	 */
	protected function _release_order($id, $status, $options=Array()){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('Order id is Null');
        }
        if(!isset($status)){
            throw new Sher_Core_Model_Exception('Order state is Null');
        }
        
		$updated = array(
			'state' => $status,
		);
		
		// 已过期关闭订单
		if ($status == Sher_Core_Util_Constant::ORDER_EXPIRED){
			$updated['is_closed'] = 1;
			$updated['closed_date'] = time();
		}
		
		// 已取消订单
		if ($status == Sher_Core_Util_Constant::ORDER_CANCELED){
			$updated['is_canceled'] = 1;
			$updated['canceled_date'] = time();
		}

      //支付成功
      if($status == Sher_Core_Util_Constant::ORDER_PUBLISHED){
        $updated['is_finished'] = 1;
        $updated['finished_date'] = time();
        $updated['trade_no'] = $options['trade_no'];
        $updated['trade_site'] = $options['trade_site'];
      }

      //更新订单状态 
      $ok = $this->update_set((int)$id, $updated);

      if($ok){
        //支付成功
        if($status == Sher_Core_Util_Constant::ORDER_PUBLISHED){
          $order = $this->find_by_id((int)$id);
          // 如果是大于等于包月并且类型是实验室,自动创建会员账号
          if($order['kind']==self::KIND_VIP && in_array((int)$order['item_id'], array(2,3,4,5))){
            $member_model = new Sher_Core_Model_DMember();
            $member_model->gen_d3in_member($order['user_id'], array('item_id'=>(int)$order['item_id'], 'pay_money'=>$order['pay_money']));
          }
          //更新预约表状态
          if($order['kind']==self::KIND_D3IN){
            $appoint_id = $order['item_id'];
            $appoint_model = new Sher_Core_Model_DAppoint();
            $appoint = $appoint_model->load($appoint_id);
            if($appoint && $appoint['state']==Sher_Core_Model_DAppoint::STATE_PAY){
              $appoint_model->finish_appoint($appoint_id);
            }
          }
        }
      }

      return $ok;
	}

	/**
	 * 获取来源站点
	 */
	protected function get_from_label($site){
		switch($site){
			case Sher_Core_Util_Constant::FROM_LOCAL:
				$label = '官网';
				break;
			case Sher_Core_Util_Constant::FROM_WEIXIN:
				$label = '微信小店';
				break;
			case Sher_Core_Util_Constant::FROM_WAP:
				$label = '手机网页';
				break;
			case Sher_Core_Util_Constant::FROM_IAPP:
				$label = '手机应用';
				break;
			default:
				$label = '其他';
				break;
		}
		return $label;
	}
	
}
