<?php
/**
 * 专辑 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_Albums extends Sher_Core_Model_Base {

    protected $collection = "albums";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 专辑名称
		'title' => '',
        # 所属描述
        'des' => '',
        # 封面图
        'cover_id' => '',
		# banner图
		'banner_id' => '',
		# 图片数组
		'asset' => array(),
		# 用户id
		'user_id' => 0,
		# 图片数量
		'asset_count' => 0,
        # 浏览量
        'view_count' => 0,
        # 点赞数
        'love_count' => 0,
        # 评论数
        'comment_count' => 0,
		# 关注数
		'favorite_count' => 0,
		# 商品数量
		'product_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'user_id', 'asset_count','view_count','love_count','favorite_count','comment_count','product_count');
	protected $float_fields = array();
	protected $counter_fields = array('view_count','love_count','favorite_count','comment_count','product_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
		'banner' => array('banner_id' => 'Sher_Core_Model_Asset'),
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
	 * 删除某图片
	 */
	public function delete_asset($id, $asset_id){
		
		// 从图片数组中删除
		$criteria = $this->_build_query($id);
		self::$_db->pull($this->collection, $criteria, 'asset', $asset_id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}
	
	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		
		if(is_null($id)){
			$id = $this->id;
		}
		
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    
		if(is_null($id)){
	        $id = $this->id;
	    }
		
	    if(empty($id)){
	        return false;
	    }
		
		if(!$force){
			$albums = $this->find_by_id((int)$id);
			if(!isset($albums[$field_name]) || $albums[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
}
