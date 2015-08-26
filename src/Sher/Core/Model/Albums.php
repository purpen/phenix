<?php
/**
 * 专辑分类 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_Albums extends Sher_Core_Model_Base {

    protected $collection = "albums";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 投票名称
		'title' => '',
        # 所属描述
        'des' => '',
		# 用户id
		'user_id' => 0,
        # 浏览量
        'views' => 0,
        # 点赞数
        'likes' => 0,
		# 产品id
		'pid' => 0,
		# 专辑分类id
		'dadid' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array();
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
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
