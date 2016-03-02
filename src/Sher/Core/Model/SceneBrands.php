<?php
/**
 * 品牌 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneBrands extends Sher_Core_Model_Base {

    protected $collection = "scene_brands";
	
    protected $schema = array(
		# 标题
		'title' => '',
        # 描述
        'des' => '',
		# 封面
		'cover_id' => '',
        # 点击次数
        'used_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title','des','cover_id');
	protected $int_fields = array('status', 'used_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count');
	protected $retrieve_fields = array();
    
	protected $joins = array();
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        // 获取封面图
		if(isset($row['cover_id'])){
			$row['cover'] = $this->cover($row['cover_id']);
		}
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
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
	}
	
	/**
	 * 获取封面图
	 */
	protected function cover($cover_id){
		// 已设置封面图
		if(!empty($cover_id)){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($cover_id);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$cover_id,
			'asset_type' => Sher_Core_Model_Asset::TYPE_SPECIAL_COVER
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}
}