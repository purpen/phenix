<?php
/**
 * 专辑 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_Album extends Sher_Core_Model_Base {

    protected $collection = "album";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 专辑名称
		'title' => '',
        # 所属描述
        'des' => '',
        # 封面图
        'cover_id' => '',
		# 图片数组
		'asset' => array(),
		'asset_count' => 0,
		# 用户id
		'user_id' => 0,
        # 浏览量
        'view_count' => 0,
        # 点赞数
        'love_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array();
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
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
