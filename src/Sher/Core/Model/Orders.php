<?php
/**
 * 订单列表
 * @author purpen
 */
class Sher_Core_Model_Orders extends Sher_Core_Model_Base {

    protected $collection = "orders";
	
	# 3 days
	const WAIT_TIME = 3;
	
	# 默认运费
	private $_fees = 0;
	
    protected $schema = array(
		
		## 订单金额
		
		'pay_money'   => 0,
		'total_money' => 0,
		'card_money'  => 0,
		'coin_money'  => 0,
		'freight'     => 0,
		
		## 折扣
		
		'discount' => 0,
		
		## 用户
		
		'user_id' => null,
		'name' => null,
		'email' => null,
		'province' => null,
		'city' => null,
		'area' => null,
		'address' => null,
		'zip' => null,
		'phone' => null,
		
		## 发票信息
		
		'is_invoiced' => 0,
		'invoice_type' => 0,
		'invoice_caty' => 0,
		'invoice_title' => '',
		'invoice_content' => '',
		
		## 支付信息
		
		'is_payed' => 0,
		'pay_date' => 0,
		'cancel_date' => 0,
		'pay_away' => 0,
		
		## 物流信息
		
		'transfer' => '',
		'transfer_time' => '',
		
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
    );

	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id');

	protected $joins = array(
	    'user' =>   array('user_id' => 'Sher_Core_Model_User')
	);
	
    /**
     * 付款方式
     * 
     * @var array
     */
    private $payment_methods = array(
        'a'=>array(
            'name'=>'在线支付',
            'summary'=>'支付宝作为诚信中立的第三方机构，充分保障货款安全及买卖双方利益,支持各大银行网上支付。'
        )
    );
	
    /**
     * 配送方式
     * 
     * @var array
     */
    private $transfer_methods = array(
        'a'=>array(
            'name'=>'普通快递',
            'freight'=>0
        )
    );
	
    /**
     * 送货时间
     * 
     * @var array
     */
    private $transfer_time = array(
        'a'=>'任意时间',
        'b'=>'星期一至星期五',
        'c'=>'星期六、日'
    );
	
    /**
     * 发票的内容类型
     */
    private $invoice_caty = array(
        'd'=>'明细',
        'o'=>'办公用品',
        's'=>'数码'
    );
	
    /**
     * 获取快递费用
     */
	public function validate_express_fees($city, $overweight=false){
		
	}
	
    /**
     * 重新计算订单的金额
     * 
     * @return string
     */
    public function recalculate_order_amount($order_id){
		
    }
	
    /**
     * 添加订单明细信息
     */
    public function add_order_detail($user_id,$product_id,$sale_price,$price,$quantity=1,$size=null){
		
    }
	
	
}
?>