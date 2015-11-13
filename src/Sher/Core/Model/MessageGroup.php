<?php
/**
 * 私信分组 Model
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_MessageGroup extends Sher_Core_Model_Base {

    protected $collection = "message_group";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
	
    protected $schema = array(
        
		# 标题
        'title' => 0,
		# 描述
		'des' => '',
		# 关联用户id
        'user_ids' => array(),
		# 创建用户id
		'user_id' =>0,
    );
	
	protected $required_fields = array('title','des');
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
