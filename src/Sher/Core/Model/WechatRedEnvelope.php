<?php
/**
 * 微信红包信息记录表
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_WechatRedEnvelope extends Sher_Core_Model_Base {

    protected $collection = "redenvelope";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 所属分类
	const TYPE = 1; // 易购用户
	
    protected $schema = array(

		'return_code' => '', // 返回状态码
        'result_code' => '', // 结果状态码
        'return_msg' => '', // 红包支付状态信息
        'mch_billno' => 0, // 商户订单号
        'mch_id' => 0, // 微信支付分配的商户号
        'wxappid' => '', // 商户appid
        'openid' => '', // 接受收红包的用户openid
        'total_amount' => 0, // 付款金额，单位分
        'send_listid' => 0, // 红包订单的微信单号
        'send_time' => '' // 红包发送时间
    );
	
	protected $required_fields = array();
	protected $int_fields = array('type','user_id');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
    // 添加关联表
	protected $joins = array(
	    
	);
	
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
        parent::after_save();
    }
}