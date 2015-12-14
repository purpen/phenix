<?php
/**
 * app专题
 * @author tianshuai
 */
class Sher_Core_Model_SpecialSubject extends Sher_Core_Model_Base  {
	
	protected $collection = "special_subject";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
  
	##常量
	#类型:1.自定义内容；2.指定商品ID
	const KIND_CUSTOM = 1;
	const KIND_APPOINT = 2;
	  
	protected $schema = array(
	  'title' => null,
	  'cover_id' => null,
	  'banner_id' => null,
	  # 分类ID
	  'category_id' => null,
	  # 商品ID数组
	  'products' => array(),
	  # 内容
	  'content' => null,
	  # 简述
	  'summary' => null,
	  'tags' => array(),
	  //备注
	  'remark'  => null,
	  'user_id' => 0,
	  'kind' => self::KIND_CUSTOM,
	  'stick' => 0,
	  'state' => 1,
	  'view_count' => 0,
	  'comment_count' => 0,
	  'love_count' => 0,
	  'favorite_count' => 0,
	);

	protected $required_fields = array('user_id', 'title', 'category_id');
  
	protected $int_fields = array('state', 'user_id', 'kind', 'stick', 'view_count', 'comment_count', 'love_count', 'favorite_count');
  
	protected $counter_fields = array('view_count', 'comment_count', 'love_count', 'favorite_count');

	protected $joins = array(
		'category' => array('category_id' => 'Sher_Core_Model_Category'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		// 去除 html/php标签
		if(isset($row['remark'])){
			  $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
		}

		// 获取封面图
		$row['cover'] = $this->cover($row['cover_id']);

		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
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
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SPECIAL_COVER
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
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
}

