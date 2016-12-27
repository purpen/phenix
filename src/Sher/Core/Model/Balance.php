<?php
/**
 * 拥金表
 * @author tianshuai
 */
class Sher_Core_Model_Balance extends Sher_Core_Model_Base  {
	protected $collection = "balance";

    ## 类型
    const TYPE_PERSON = 1;
    const TYPE_COMPANY = 2;

    ## 进度
    const STAGE_ING = 1; // 进行中
    const STAGE_REFUND = 2; // 退款
    const STAGE_FINISH = 5; // 已完成
	
	protected $schema = array(
        'user_id' => 0,
        # 联盟账户
        'alliance_id' => null,
        'order_rid' => null,
        'sub_order_id' => null,
        'product_id' => 0,
        'sku_id' => 0,
        'quantity' => 1,
        # 拥金比例
        'commision_percent' => 0,
        # 拥金单价
        'unit_price' => 0,
        # 拥金总额 单价*数量
        'total_price' => 0,
        # 推广码
        'code' => null,
        # 备注
        'summary'  => null,

        # 类型
        'type' => self::TYPE_PERSON,
        # 类型: 1.链接推广；2.地盘；3.--
        'kind' => 1,
        # 所属订单商品状态: 1.未完成(进行中); 2.退款；5.完成(可以结算)
        'stage' => self::STAGE_ING,
        # 状态: 0.未结算；1.已结算；
		'status' => 0,

  	);

    protected $required_fields = array('order_rid', 'product_id', 'user_id', 'alliance_id');

    protected $int_fields = array('status', 'user_id', 'kind', 'type', 'product_id', 'sku_id', 'quantity', 'stage');
	protected $float_fields = array('commision_percent', 'unit_price', 'total_price');
	protected $counter_fields = array();


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){
            $user_id = $this->data['alliance_id'];

            // 更新关联用户表
            //$alliance_model = new Sher_Core_Model_Alliance();

        }
    }


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

