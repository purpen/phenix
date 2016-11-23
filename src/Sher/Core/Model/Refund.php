<?php
/**
 * 退货/退款
 * @author tianshui
 */
class Sher_Core_Model_Refund extends Sher_Core_Model_Base {

    protected $collection = "refund";
	
	# 产品周期stage
    const STAGE_CANCEL = 0;
    const STAGE_APPLY = 1;
    const STAGE_ING = 2;
    const STAGE_FINISH = 3;
	
    protected $schema = array(
        # 退款编号
		'rid'     => null,
        # 站外编号(与erp对应)
        'number'    => 0,
        'user_id'   => 0,
        # sku或商品ID
        'target_id' => 0,
        # 商品类型：1.sku；2.商品；3.--;
        'target_type' => 1,
        'product_id' => 0,
        'order_id' => null,
        'sub_order_id' => null,
        'refund_price' => 0,
        'quantity' => 1,
        # 1.退款；2.退货；3.返修；4.--；
        'kind' => 1,
        // 0.取消；1.申请退货款；2.进行中；3.已完成；4.拒绝;
        'stage' => self::STAGE_APPLY,
        # 拒绝原因
        'summary' => null,
        'status' => 1,
        'deleted' => 0,

    );
	
	protected $required_fields = array('user_id', 'product_id', 'order_id', 'rid');
	protected $int_fields = array('user_id','target_id','target_type','product_id','kind','stage','status','number','deleted');
	protected $float_fields = array('refund_price');
	protected $counter_fields = array();
	protected $retrieve_fields = array();
	protected $joins = array(

	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}

	// 添加自定义ID
    protected function before_insert(&$data) {
		
		parent::before_insert($data);
    }


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

		// 新建数据,补全默认值
		if ($this->is_saved()){
			$data['rid'] = $this->gen_refund_id($data['_id'], '1');
		}

        if(empty($data['number'])){
            $data['number'] = Sher_Core_Helper_Util::getNumber();
        }
		
	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){

    }

	/**
	 * 通过rid查找
	 */
	public function find_by_rid($rid){
		$row = $this->first(array('rid'=>(int)$rid));
        if (!empty($row)) {
            $row = $this->extended_model_row($row);
        }
		return $row;
	}
	
	/**
	 * 通过number查找
	 */
	public function find_by_number($number){
		$row = $this->first(array('number'=>(int)$number));
        if (!empty($row)) {
            $row = $this->extended_model_row($row);
        }
		return $row;
	}
	
	
	/**
	 * 更新产品的状态阶段
	 */
	public function mark_as_stage($id, $stage) {
		return $this->update_set($id, array('stage' => (int)$stage));
	}

	/**
	 * 生成产品的SKU, SKU十位数字符
	 */
	protected function gen_refund_id($id, $prefix='1'){
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
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$product = $this->find_by_id((int)$id);
			if(!isset($product[$field_name]) || $product[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}


    /**
     * 逻辑删除
     */
    public function mark_remove($id){
        $ok = $this->update_set((int)$id, array('deleted'=>1));
        return $ok;
    }
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}

	
}
