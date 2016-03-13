<?php
/**
 * 情景 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneScene extends Sher_Core_Model_Base {

    protected $collection = "scene_scene";
	
    protected $schema = array(
		# 标题
		'title' => '',
        # 分类
        'type' => '',
		# 图片地址
		'images' => '',
        # 使用次数
        'used_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title','type','images');
	protected $int_fields = array('status', 'used_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count');
	protected $retrieve_fields = array();
    
	protected $joins = array();
	
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
