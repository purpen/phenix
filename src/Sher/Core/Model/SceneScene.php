<?php
/**
 * 情景 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneScene extends Sher_Core_Model_Base {

    protected $collection = "scene_scene";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 标题
		'title' => '',
		# 创建者
		'user_id' => 0,
		# 描述
		'des' => '',
        # 分类
        'type' => '',
		
		# 场景
		'sight' => array(),
		# 标签
		'tags' => array(),
		# 地理位置
		 'location'  => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(),
        ),
		# 地址
        'address' => '',
		
        # 封面
		'cover_id' => '',
		
		# 使用次数
        'used_count' => 0,
		# 浏览数
    	'view_count' => 0,
		# 订阅数
        'subscription_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 评论数 
    	'comment_count' => 0,
		
		# 审核
		'is_check' => 1,
		# 精选
		'stick' => 0,
		# 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'used_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'cover' =>  array('cover_id' => 'Sher_Core_Model_Asset'), 
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
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}
}
