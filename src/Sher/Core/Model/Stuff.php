<?php
/**
 * 产品分享
 * @author purpen
 */
class Sher_Core_Model_Stuff extends Sher_Core_Model_Base {

    protected $collection = "stuff";

    protected $schema = array(
	    'user_id' => 0,
		
		# 类别支持多选
		'category_id' => 0,
		# 分类父级
		'fid' => 0,
		
	    'title' => '',
        'description' => '',
    	'tags' => array(),
        'like_tags' => array(),
		
 		'asset_id' => '',
		
    	'view_count' => 0,
        'favorite_count' => 0, #收藏数
        'love_count' => 0, #喜欢数
    	'comment_count' => 0, #回应数
		
    	'deleted' => 0,
    	'published' => 1,
		
		'random' => 0
    );

	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id','deleted','view_count','favorite_count','comment_count');
	
	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	    'asset' => array('asset_id' => 'Sher_Core_Model_Asset'),
		'category' => array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = Sher_Core_Helper_Url::stuff_view_url($row['_id']);
		$row['tags_s'] = !empty($row['tags']) ? implode(',', $row['tags']) : '';
		$row['fav_tags'] = !empty($row['like_tags']) ? implode(',', $row['like_tags']) : '';

		if (isset($row['asset'])) {
	        $row['thumb_small_view_url'] = $row['asset']['thumb_small_url'];
	        $row['thumb_big_view_url'] = $row['asset']['thumb_big_url'];
		}else{
	        $row['thumb_small_view_url'] = Doggy_Config::$vars['app.url.default_thumb_small'];
	        $row['thumb_big_view_url'] = Doggy_Config::$vars['app.url.default_thumb_big'];
		}
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
	    }
		
		// 获取父级类及类组
		if (isset($data['category_id'])){
			$category = new Sher_Core_Model_Category();
			$result = $category->find_by_id((int)$data['category_id']);
			if (empty($result)){
				throw new Sher_Core_Model_Exception('所选分类出错！');
			}
			$data['fid'] = $result['pid'];
		}
		
		// 添加随机数
		$data['random'] = Sher_Core_Helper_Util::gen_random();
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
  	protected function after_save() {
    	// 如果是新的记录
    	if($this->insert_mode){
      	  	$category_id = $this->data['category_id'];
      	  	$fid = $this->data['fid'];
      
      	  	$category = new Sher_Core_Model_Category();
      	  	if(!empty($category_id)){
        		$category->inc_counter('total_count', 1, $category_id);
      	  	}
      	  	if(!empty($fid)){
        		$category->inc_counter('total_count', 1, $fid);
      	  	}
    	}
  	}
  
	/**
	 * 更新喜欢数据
	 *
	 * @param string $stuff_id
	 * @param array $tags
	 * @return true or false
	 */
	public function update_like($stuff_id, $tags, $is_add=1){	//增加默认参数is_add，如果是更新，不增加like_count
		$query = array();
		$update = array();
	    $query['_id'] = new MongoId($stuff_id);
		$update['$addToSet']['like_tags'] = array('$each'=>$tags);
		if ($this->update($query, $update,false,true) && $is_add) {
			return $this->inc($query,'like_count');
		} else {
			return true;
		}
		return false;
	}

	/**
	 * 更新标签
	 */
	public function update_tag($stuff_id, $new_tag, $filed_name='like_tags'){
		$query = array();
	    $update = array();
	    $query['_id'] = new MongoId($stuff_id);
	    $update['$addToSet'][$filed_name] = array('$each'=>$new_tag);
	    return $this->update($query, $update, false, true);
	}
	
	/**
	 * 增加计数
	 */
	public function inc_counter($count_name, $stuff_id=null){
		if(is_null($stuff_id)){
			$stuff_id = $this->id;
		}
		if(empty($stuff_id)){
			return false;
		}
		
		return $this->inc($stuff_id, $count_name);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($count_name,$stuff_id=null,$force=false){
	    if(is_null($stuff_id)){
	        $stuff_id = $this->id;
	    }
	    if(empty($stuff_id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($stuff_id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($stuff_id, $count_name);
	}

	/**
	 * 处理text_index 只有一个操作，所以不建新的类，在这里补充一下
	 */
	public function remove_all_links($stuff_id) {
		if(empty($stuff_id)){
			return false;
		}
		$query['_id'] = new MongoId($stuff_id);
		$this->remove($query);

		//删除索引
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id'=>$stuff_id));
		unset($textindex);

		//删除asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id'=>$stuff_id));
		unset($asset);
		
		return true;
	}

}
?>