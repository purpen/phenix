<?php
/**
 * 订单列表
 * @author purpen
 */
class Sher_Core_Model_Orders extends Sher_Core_Model_Base {

    protected $collection = "orders";
	
	# 3 days
	const WAIT_TIME = 3;
	
    protected $schema = array(
		# 订单编号
		'rid' => 0,
		## 订单明细项
		#
		# product_id, size, quantity
		# price, discount, true_price
		# 
		'items' => array(),
		'items_count' => 0,
		
		## 订单金额
		
		'pay_money'   => 0,
		'total_money' => 0,
		'card_money'  => 0,
		'coin_money'  => 0,
		
		# 物流费用
		'freight'  => 0,
		
		# 折扣
		'discount' => 0,
		
		## 用户
		
		'user_id' => null,
		
		## 收货地址
		'addbook_id' => null,
		
		## 发票信息
		'invoice_type' => 0,
		'invoice_caty' => 0,
		'invoice_title' => '',
		'invoice_content' => '',
		
		## 支付信息
		'payment_method' => 0,
		
		'is_payed' => 0,
		'payed_date' => 0,
		
		# 取消订单标识及时间
		'is_canceled' => 0,
		'canceled_date' => 0,
		
		## 物流信息
		
		'transfer' => '',
		'transfer_time' => '',
		
		## 快递单号，发货时间
		
		'express_no' => '',
		'sended_date' => 0,
		
		## 第三方交易号
		'trade_no' => 0,
		
		## 备注
		
		'summary' => '',
		
		## 安全信息
		
		'ip' => '',
		'sesid' => '',
		'referer' => '',
		'fromword' => '',
		
		## 优惠码
		
		'card_code' => '',
		
		## 订单状态
		'status' => 0,
		
		## 时间（完成）
		'finished_date' => 0,
		
    );

	protected $required_fields = array('rid', 'user_id');
	protected $int_fields = array('user_id','invoice_type');

	protected $joins = array(
	    'user' => array('user_id' => 'Sher_Core_Model_User'),
		'addbook' => array('addbook_id' => 'Sher_Core_Model_AddBooks'),
	);
	
	protected function extra_extend_model_row(&$row) {
		$row['status_label'] = $this->get_order_status_label($row['status']);
		if ($row['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT && $row['payment_method'] == 'a'){
			$row['pay_url'] = Doggy_Config::$vars['app.url.alipay'];
		}
		
		$row['payment'] = $this->find_payment_methods($row['payment_method']);
	}
	
	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
		
		$this->validate_order_items($data);
		
	    parent::before_save($data);
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
	 * 过滤items
	 */
	protected function validate_order_items(&$data){
		$item_fields = array('sku', 'size', 'quantity', 'price', 'sale_price');
		$int_fields = array('sku', 'quantity');
		$float_fields = array('price', 'sale_price');
		
		$new_items = array();
		for($i=0; $i<count($data['items']); $i++){
	        foreach ($item_fields as $f) {
	            if (isset($data['items'][$i][$f])) {
					if (in_array($f, $int_fields)){
						$new_items[$i][$f] = (int)$data['items'][$i][$f];
					}elseif(in_array($f, $float_fields)){
						$new_items[$i][$f] = floatval($data['items'][$i][$f]);
					}else{
						$new_items[$i][$f] = $data['items'][$i][$f];
					}
	            }
	        }
		}
		
		$data['items'] = $new_items;
	}
	
	/**
	 * 订单状态标签
	 */
	protected function get_order_status_label($status){
		switch($status){
			case Sher_Core_Util_Constant::ORDER_EXPIRED:
				$status_label = '已过期订单';
				break;
			case Sher_Core_Util_Constant::ORDER_CANCELED:
				$status_label = '已取消订单';
				break;
			case Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT:
				$status_label = '等待付款';
				break;
			case Sher_Core_Util_Constant::ORDER_WAIT_CHECK:
				$status_label = '等待审核';
				break;
			case Sher_Core_Util_Constant::ORDER_READY_GOODS:
				$status_label = '正在配货';
				break;
			case Sher_Core_Util_Constant::ORDER_SENDED_GOODS:
				$status_label = '已发货';
				break;
			case Sher_Core_Util_Constant::ORDER_PUBLISHED:
				$status_label = '已完成';
				break;
		}
		
		return $status_label;
	}
	
    /**
     * 付款方式
     * 
     * @var array
     */
    private $payment_methods = array(
        array(
			'id' => 'a',
            'name' => '在线支付',
            'summary' => '支付宝作为诚信中立的第三方机构，充分保障货款安全及买卖双方利益,支持各大银行网上支付。'
        ),
    );
	
    /**
     * 配送方式
     * 
     * @var array
     */
    private $transfer_methods = array(
        array(
			'id' => 'a',
            'name' => '免费配送',
            'freight'=> 0,
        ),
    );
	
    /**
     * 送货时间
     * 
     * @var array
     */
    private $transfer_time = array(
		array(
			'id' => 'a',
			'title' => '任意时间',
		),
		array(
			'id' => 'b',
			'title' => '星期一至星期五',
		),
		array(
			'id' => 'c',
			'title' => '星期六、日',
		),
    );
	
    /**
     * 发票的内容类型
     */
    private $invoice_caty = array(
		array(
			'id' => 'd',
			'title' => '明细',
		),
		array(
			'id' => 'o',
			'title' => '办公用品',
		),
		array(
			'id' => 's',
			'title' => '数码配件',
		),
    );
	
    /**
     * 重新计算订单的金额
     * 
     * @return string
     */
    public function recalculate_order_amount($order_id){
		
    }
	
    /**
     * 返回对应的抬头类型
     * 
     * @param $key
     * @return mixed
     */
    public function find_invoice_category($key=null){
        if(is_null($key)){
            return $this->invoice_caty;
        }
		
		for($i=0;$i<count($this->invoice_caty);$i++){
			if ($this->invoice_caty[$i]['id'] == $key){
				return $this->invoice_caty[$i];
			}
		}
		
		return null;
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
     * 返回对应的送货方式
     * 
     * @param string $key
     * @return mixed
     */
    public function find_transfer_methods($key=null){
        if(is_null($key)){
            return $this->transfer_methods;
        }
		
		for($i=0;$i<count($this->transfer_methods);$i++){
			if ($this->transfer_methods[$i]['id'] == $key){
				return $this->transfer_methods[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的送货时间
     * 
     * @param string $key
     * @return mixed
     */
    public function find_transfer_time($key=null){
        if(is_null($key)){
            return $this->transfer_time;
        }
		
		for($i=0;$i<count($this->transfer_time);$i++){
			if ($this->transfer_time[$i]['id'] == $key){
				return $this->transfer_time[$i];
			}
		}
		
        return null;
    }
	
}
?>