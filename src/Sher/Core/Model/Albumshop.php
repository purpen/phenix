<?php
/**
 * 产品名称 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_Albumshop extends Sher_Core_Model_Base {

    protected $collection = "albumshop";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 用户id
		'user_id' => 0,
		# 产品id
		'pid' => 0,
		# 专辑分类id
		'dadid' => 0,
		# 浏览量
        'view_count' => 0,
        # 点赞数
        'love_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('user_id','pid','dadid');
	protected $int_fields = array('user_id','pid','dadid');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	    'product' => array('pid' => 'Sher_Core_Model_Product'),
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
