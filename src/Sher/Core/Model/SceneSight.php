<?php
/**
 * 场景 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneSight extends Sher_Core_Model_Base {

    protected $collection = "scene_sight";
	
    protected $schema = array(
		# 标题
		'title' => '',
		# 创建者
		'user_id' => 0,
		# 描述
		'des' => '',
        # 分类
        'type' => '',
		
		# 所属情景
		'scene_id' => 0,
		# 标签
		'tags' => array(),
		# 产品
		'product' => array(),
		# 产品位置坐标
		'product_site' => array(
			'x' => 0,
			'y' => 0
		),
		# 地理位置
		 'location'  => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(
				'longitude' => 0,
				'latitude' => 0
			),
        ),
		# 地址
        'address' => '',
		
        # 封面
		'cover_id' => '',
		
		# 使用次数
        'used_count' => 0,
		# 浏览数
    	'view_count' => 0,
		# 喜欢数
        'love_count' => 0,
		# 评论数 
    	'comment_count' => 0,
		
		# 精选
		'fine'  => 0,
		# 审核
		'is_check' => 1,
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
