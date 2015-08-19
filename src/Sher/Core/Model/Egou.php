<?php
/**
 * egou合作会员信息表
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_Egou extends Sher_Core_Model_Base {

    protected $collection = "egou";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 所属分类
	const TYPE = 1; // 易购用户
	
    protected $schema = array(

		# 易购会员的唯一标识(string)
		'eid' => '',
        # 活动id(string)
        'hid' => '',
        # 所属分类(int)
        'type' => self::TYPE,
		# 用户id(int)
		'user_id' => 0,
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