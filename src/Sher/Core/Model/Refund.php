<?php
/**
 * 退货/退款
 * @author tianshui
 */
class Sher_Core_Model_Refund extends Sher_Core_Model_Base {

    protected $collection = "refund";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
	
	# 产品周期stage
    const STAGE_CANCEL = 0;
    const STAGE_ING = 1;
    const STAGE_FINISH = 2;
    const STAGE_REJECT = 3;
	
    protected $schema = array(
        # 退款编号
		'_id'     => null,
        # 站外编号(与erp对应)
        'number'    => 0,
        'user_id'   => 0,
        # sku或商品ID
        'target_id' => 0,
        # 商品类型：1.sku；2.商品；3.--;
        'target_type' => 1,
        'product_id' => 0,
        'order_rid' => null,
        'sub_order_id' => null,
        'refund_price' => 0,
        # 运费
        'freight' => 0,
        'quantity' => 1,
        # 1.退款；2.退货；3.返修；4.--；
        'type' => 1,
        // 0.取消；1.进行中；2.已完成；3.拒绝;
        'stage' => self::STAGE_ING,
        # 退款原因
        'reason' => 1,
        # 退款说明
        'content' => '',
        # 拒绝原因
        'summary' => null,
        'status' => 1,
        'deleted' => 0,
        # 退款时间
        'refund_on' => 0,

    );
	
	protected $required_fields = array('user_id', 'product_id', 'order_rid');
	protected $int_fields = array('user_id','target_id','target_type','product_id','type','stage','status','number','deleted','reason');
	protected $float_fields = array('refund_price', 'freight');
	protected $counter_fields = array();
	protected $retrieve_fields = array();
	protected $joins = array(

	);

    /**
     * 退款原因
     */
    private $refund_reason = array(
		array(
			'id' => 1,
			'title' => '不喜欢/不想要了',
		),
		array(
			'id' => 2,
			'title' => '未按约定时间发货',
		),
		array(
			'id' => 3,
			'title' => '快递/物流没送到',
		),
    );

    /**
     * 退货原因
     */
    private $return_reason = array(
		array(
			'id' => 1,
			'title' => '收到商品破损',
		),
		array(
			'id' => 2,
			'title' => '商品发货/漏发',
		),
		array(
			'id' => 3,
			'title' => '商品需要维修',
		),
		array(
			'id' => 4,
			'title' => '收到商品与描述不符',
		),
		array(
			'id' => 5,
			'title' => '商品质量问题',
		),
    );
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        if($row['reason']==1){
            $reason = $this->find_refund_reason($row['reason']);
            $row['reason_label'] = $reason['title'];
        }elseif($row['reason']==2){
            $reason = $this->find_return_reason($row['reason']); 
            $row['reason_label'] = $reason['title'];
        }else{
            $row['reason_label'] = '';
        }

        switch($row['type']){
            case 0:
                $row['type_label'] = '取消退款申请';
                break;
            case 1:
                $row['type_label'] = '退款中';
                break;
            case 2:
                $row['type_label'] = '退款成功';
                break;
            case 3:
                $row['type_label'] = '拒绝退款';
                break;
            default:
                $row['type_label'] = '';
        }

	}

	// 添加自定义ID
    protected function before_insert(&$data) {
		$data['_id'] = $this->gen_refund_id();
		parent::before_insert($data);
    }


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

		// 新建数据,补全默认值
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
	 * 生成产品的SKU, SKU十位数字符
	 */
	protected function gen_refund_id($prefix='1'){
		$name = Doggy_Config::$vars['app.refund_serialno.name'];
		
		$sku  = $prefix;
		$val = $this->next_seq_id($name);
		
		$len = strlen((string)$val);
		if ($len <= 5) {
			$sku .= date('md');
			$sku .= sprintf("%05d", $val);
		}else{
			$sku .= substr(date('md'), 0, 9 - $len);
			$sku .= $val; 
		}
		
		Doggy_Log_Helper::debug("Gen to refund [$sku]");
		
		return (int)$sku;
	}
	
	
	/**
	 * 更新退款单的状态阶段
	 */
	public function mark_as_stage($id, $stage) {
		return $this->update_set($id, array('stage' => (int)$stage));
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

    /**
     * 返回对应的退款原因
     * 
     * @param $key
     * @return mixed
     */
    public function find_refund_reason($key=null){
        if(is_null($key)){
            return $this->refund_reason;
        }
		
		for($i=0; $i<count($this->refund_reason);$i++){
			if ($this->refund_reason[$i]['id'] == $key){
				return $this->refund_reason[$i];
			}
		}
		
		return null;
    }

    /**
     * 返回对应的退货原因
     * 
     * @param $key
     * @return mixed
     */
    public function find_return_reason($key=null){
        if(is_null($key)){
            return $this->return_reason;
        }
		
		for($i=0; $i<count($this->return_reason);$i++){
			if ($this->return_reason[$i]['id'] == $key){
				return $this->return_reason[$i];
			}
		}
		
		return null;
    }

	
}
