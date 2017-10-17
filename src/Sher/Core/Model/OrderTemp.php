<?php
/**
 * 临时订单信息
 * @author purpen
 */
class Sher_Core_Model_OrderTemp extends Sher_Core_Model_Base  {

    protected $collection = "ordertemp";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		'rid' => 0,
		'user_id' => 0,
		'dict' => array(),
		'expired'  => 0,
    # 是否来自购物车
		'is_cart' => 0,
    # 是否是预售订单
    'is_presaled' => 0,
    # 是否是活动订单: 1.普通订单; 2.page抢购； 3.app闪购
    'kind' => 1,
    # 是否是京东开普勒订单
    'is_vop' => 0,
    # 推广码 
    'referral_code' => null,
    'storage_id' => null,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'expired');
    protected $int_fields = array('rid', 'user_id', 'expired', 'is_cart', 'is_vop');
	
	
	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
		// 新建数据,补全默认值
		if ($this->is_saved()){
			$data['rid'] = $this->gen_order_id($data['_id'], '1');
		}
		
	    parent::before_save($data);
	}
	
	/**
	 * 生成订单编号, 十位数字符
	 */
	protected function gen_order_id($id, $prefix='1'){
		
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
	 * 使用红包
	 */
	public function use_bonus($rid, $code, $money) {
		$criteria = array(
			'rid' => $rid
		);
		$updated = array(
			'dict.card_code'  => $code,
			'dict.card_money' => $money,
		);
		return $this->update_set($criteria, $updated);
	}
	
	/**
	 * 使用礼品码
	 */
	public function use_gift($rid, $code, $money) {
		$criteria = array(
			'rid' => $rid
		);
		$updated = array(
			'dict.gift_code'  => $code,
			'dict.gift_money' => $money,
		);
		return $this->update_set($criteria, $updated);
	}

	/**
	 * 使用鸟币
	 */
	public function use_bird_coin($rid, $bird_coin_count, $bird_coin_money) {
		$criteria = array(
			'rid' => $rid
		);
		$updated = array(
			'dict.bird_coin_count'  => $bird_coin_count,
			'dict.bird_coin_money' => $bird_coin_money,
		);
		return $this->update_set($criteria, $updated);
	}
	
    /**
     * 验证是否存在某个订单的临时信息
     */
    public function validate_exist_order($id){
		
    }
	
    /**
     * 清除过期的临时数据
     */
    protected function clean_expired_order(){
		
    }
	
}

