<?php
/**
 * app专题
 * @author tianshuai
 */
class Sher_Core_Model_SceneSubject extends Sher_Core_Model_Base  {
	
	protected $collection = "scene_subject";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
  
	##常量
	#类型:1.自定义内容；
	const KIND_CUSTOM = 1;
	  
	protected $schema = array(
		'title' => null,
		'short_title' => null,
		'cover_id' => null,
		'banner_id' => null,
		# 分类ID
		'category_id' => 0,

		# 内容
		'content' => null,
		# 简述
		'summary' => null,
		'tags' => array(),
		'user_id' => 0,
		'kind' => self::KIND_CUSTOM,
		'stick' => 0,
		'publish' => 0,
		'status' => 1,
		'view_count' => 0,
		'comment_count' => 0,
		'love_count' => 0,
		'favorite_count' => 0,
	);

	protected $required_fields = array('user_id', 'title');
  
	protected $int_fields = array('status', 'category_id', 'user_id', 'kind', 'stick', 'view_count', 'comment_count', 'love_count', 'favorite_count');
  
	protected $counter_fields = array('view_count', 'comment_count', 'love_count', 'favorite_count');

	protected $joins = array(

	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
    $row['wap_view_url'] = sprintf("%s/special_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $row['_id']);

		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}

        if(isset($row['summary'])){
                $row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
          $row['safe_summary'] = Sher_Core_Util_View::safe($row['summary']);
        }

		// 获取封面图
		if(isset($row['cover_id'])){
			  $row['cover'] = $this->cover($row['cover_id']);
		}

		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
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
			'parent_id'  => (int)$cover_id,
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
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
			$special_subject = $this->find_by_id((int)$id);
			if(!isset($special_subject[$field_name]) || $special_subject[$field_name] <= 0){
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
	* 发布操作
	*/
	public function mark_as_publish($id, $publish = 1){
		$data = $this->extend_load((int)$id);
	
		if(empty($data)){
			return array('status'=>0, 'msg'=>'内容不存在');
		}
		if($data['publish']==(int)$publish){
			return array('status'=>0, 'msg'=>'重复的操作');  
		}
		$ok = $this->update_set((int)$id, array('publish' => $publish));
		if($ok){
			return array('status'=>1, 'msg'=>'操作成功');  
		}else{
			return array('status'=>0, 'msg'=>'操作失败');   
		}
	}
	
	/**
	* 推荐操作
	*/
	public function mark_as_stick($id, $stick=1){
		$data = $this->extend_load((int)$id);
	
		if(empty($data)){
			return array('status'=>0, 'msg'=>'内容不存在');
		}
		if($data['stick']==(int)$stick){
			return array('status'=>0, 'msg'=>'重复的操作');  
		}
		$ok = $this->update_set((int)$id, array('stick' => $stick));
		if($ok){
			return array('status'=>1, 'msg'=>'操作成功');  
		}else{
			return array('status'=>0, 'msg'=>'操作失败');   
		}
	}
}

