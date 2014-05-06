<?php
/**
 * 帖子主题
 * @author purpen
 */
class Sher_Core_Model_Topic extends Sher_Core_Model_Base {

    protected $collection = "topic";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 推荐
	const STICK_EDITOR = 1;
	const STICK_HOME = 2;
	
    protected $schema = array(
	    'user_id' => null,
		# 类别支持多选
		'category_id' => 0,
		# 所属产品
		'target_id' => 0,
		
	    'title' => '',
        'description' => '',
    	'tags' => array(),
		
 		'cover_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		
		# 视频链接
		'video_url' => array(),
		
		# 是否为主题帖
		'parent_id' => '',
		
		## 计数器
		
		# 浏览数
    	'view_count' => 0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 回应数 
    	'comment_count' => 0,
		
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
		# 置顶标识
		'top'   => 0,
		# 精华标识
		'fine'  => 0,
		
    	'deleted' => 0,
		# 是否审核，默认已审核
    	'published' => 1,
		# 随机数
		'random' => 0,
		
		# 最后回复者及回复时间
		'last_reply_time' => 0,
		'last_user' => null,
    );
	
	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id','category_id','deleted','published');
	
	protected $counter_fields = array('asset_count', 'view_count', 'favorite_count', 'love_count', 'comment_count');
	
	protected $joins = array(
	    'user'      =>  array('user_id'   => 'Sher_Core_Model_User'),
		'last_user' =>  array('last_user' => 'Sher_Core_Model_User'),
		'category'  =>  array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
	    }
		
		// 添加随机数
		$data['random'] = Sher_Core_Helper_Util::gen_random();
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
    protected function after_save() {
		$category_id = $this->data['category_id'];
		if (!empty($category_id)) {
			$category = new Sher_Core_Model_Category();
			$category->inc_counter('total_count', 1, $category_id);
			unset($category);
		}
		
		$target_id   = $this->data['target_id'];
		if (!empty($target_id)) {
			$product = new Sher_Core_Model_Product();
			$product->inc_counter('topic_count', 1, $target_id);
			unset($product);
		}
    }
	
	/**
	 * 扩展Model数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = Sher_Core_Helper_Url::topic_view_url($row['_id']);
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

		if (isset($row['cover'])) {
	        $row['thumb_small_view_url'] = $row['cover']['thumb_small_url'];
	        $row['thumb_big_view_url'] = $row['cover']['thumb_big_url'];
		}else{
	        $row['thumb_small_view_url'] = Doggy_Config::$vars['app.url.default_thumb_small'];
	        $row['thumb_big_view_url'] = Doggy_Config::$vars['app.url.default_thumb_big'];
		}
		
		$row['description'] = htmlspecialchars_decode($row['description']);
	}
	
    /**
     * 标记主题为编辑推荐
     */
    public function mark_as_stick($id, $value=self::STICK_EDITOR) {
        return $this->update_set($id, array('stick' => $value));
    }
	
    /**
     * 取消主题编辑推荐
     */
	public function mark_cancel_stick($id){
		return $this->update_set($id, array('stick' => 0));
	}
	
    /**
     * 标记主题 置顶
     */
	public function mark_as_top($id){
		return $this->update_set($id, array('top' => 1));
	}
	
    /**
     * 标记主题 取消置顶
     */
	public function mark_cancel_top($id){
		return $this->update_set($id, array('top' => 0));
	}
	
    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id){
		return $this->update_set($id, array('fine' => 1));
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		return $this->update_set($id, array('fine' => 0));
	}
	
	/**
	 * 更新最后的回复者,并且comment_count+1
	 */
	public function update_last_reply($id, $user_id, $time){
		$query = array('_id'=> (int)$id);
		$new_data = array(
			'$set' => array('last_reply_time'=>$time, 'last_user'=>$user_id),
			'$inc' => array('comment_count'=>1),
		);
		return self::$_db->update($this->collection,$query,$new_data,false,false,true);
	}
	
	/**
	 * 更新标签
	 */
	public function update_tag($topic_id, $new_tag, $filed_name='like_tags'){
		$query = array();
	    $update = array();
	    $query['_id'] = (int)$topic_id;
	    $update['$addToSet'][$filed_name] = array('$each'=>$new_tag);
	    return $this->update($query, $update,false,true);
	}
	
	/**
	 * 增加计数
	 */
	public function increase_counter($field_name, $inc=1, $id=null){
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
	public function dec_counter($count_name,$topic_id=null,$force=false){
	    if(is_null($topic_id)){
	        $topic_id = $this->id;
	    }
	    if(empty($topic_id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($topic_id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		return $this->dec($topic_id, $count_name);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id));
		unset($asset);
		
		// 删除TextIndex
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id' => $id));
		unset($textindex);
		
		return true;
	}

	/**
	 * 删除某附件
	 */
	public function delete_asset($id, $asset_id){
		// 从附件数组中删除
		$criteria = $this->_build_query($id);
		self::$_db->pull($this->collection, $criteria, 'asset', $asset_id);
		
		$this->dec_counter('asset_count', $id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}
}
?>