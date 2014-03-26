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
		'category_id' => array(),
		
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
        'like_count' => 0,
		# 回应数 
    	'comment_count' => 0,
		
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
		
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
	protected $int_fields = array('user_id','deleted','published');
	
	protected $counter_fields = array('asset_count', 'view_count', 'favorite_count', 'like_count', 'comment_count');
	
	protected $joins = array(
	    'user' =>   array('user_id' => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset')
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
	}
	
    /**
     * 标记主题为编辑推荐
     */
    public function mark_as_stick($id, $value=self::STICK_EDITOR) {
        return $this->update_set($id, array('stick' => $value));
    }
	
	
	/**
	 * 更新标签
	 */
	public function update_tag($topic_id, $new_tag, $filed_name='like_tags'){
		$query = array();
	    $update = array();
	    $query['_id'] = new MongoId($topic_id);
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
	 * 处理text_index 只有一个操作，所以不建新的类，在这里补充一下
	 */
	public function remove_all_links($topic_id) {
		if(empty($topic_id)){
			return false;
		}
		$query['_id'] = new MongoId($topic_id);
		$this->remove($query);

		//删除索引
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id'=>$topic_id));
		unset($textindex);

		//删除asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id'=>$topic_id));
		unset($asset);
		
		return true;
	}

}
?>