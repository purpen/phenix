<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_Core_Model_Try extends Sher_Core_Model_Base  {

    protected $collection = "trial";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	## 参与方式
	const JOIN_FREE_AWAY = 1;
	const JOIN_PAY_AWAY  = 2;
	
	## 状态
	const STATE_DRAFT = 0;
	const STATE_PUBLISH = 1;
	
    protected $schema = array(
		'title' => '',
		# 简述，活动亮点
		'description' => '',
		# 内容，详细说明
		'content' => '',
		
		# 封面图
		'cover_id' => '',
		# banner图
		'banner_id' => '',
		
		# 浏览数
		'view_count' => 0,
		'love_count' => 0,
		'comment_count' => 0,
		
		# 活动发起人
		'user_id' => 0,
 		# 关联的产品
    	'product_id' => 0,
		
		# 试用数量
		'try_count'  => 0,
		# 申请人数
		'apply_count' => 0,
		# 申请通过的人员
		'pass_users' => array(),
		
		# 参与方式
		'join_away' => self::JOIN_FREE_AWAY,
		# 开始时间
		'start_time' => 0,
		# 结束时间
		'end_time' => 0,
		
		# 设置推荐
		'sticked' => 0,
		# 草稿、发布
		'state' => self::STATE_DRAFT,
    );
	
    protected $joins = array(
    	'product' => array('product_id' => 'Sher_Core_Model_Product'),
		'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
		'banner' => array('banner_id' => 'Sher_Core_Model_Asset'),
    );
	
    protected $required_fields = array('title', 'user_id');
	
    protected $int_fields = array('user_id', 'product_id', 'sticked', 'join_away', 'try_count', 'apply_count', 'pass_count');
	
	protected $counter_fields = array('view_count', 'love_count', 'comment_count', 'apply_count');
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	$row['view_url'] = sprintf(Doggy_Config::$vars['app.url.try.view'], $row['_id']);
		
		# 审核通过人数
		$row['pass_count'] = count($row['pass_users']);
		
		if(isset($row['content'])){
			// 转码
			$row['content'] = htmlspecialchars_decode($row['content']);
		
			// 去除 html/php标签
			$row['strip_content'] = strip_tags($row['content']);
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
	
	/**
	 * 更新通过的用户
	 */
	public function update_pass_users($id, $user_id, $is_add=true){
		if ($is_add) {
			$new_data = array(
				'$addToSet' => array('pass_users' => (int)$user_id),
			);
		} else {
			$new_data = array(
				'$pull' => array('pass_users' => (int)$user_id),
			);
		}
		
		return $this->update($id, $new_data);
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
	public function dec_counter($count_name,$id=null,$force=false){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		return $this->dec($id, $count_name);
	}
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}
	
	/**
	 * 设置Banner
	 */
	public function mark_set_banner($id, $banner_id){
		return $this->update_set($id, array('banner_id'=>$banner_id));
	}
	
    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set($id, array('sticked' => 1));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set($id, array('sticked' => 0));
	}
	
	/**
	 * 更新产品发布上线
	 */
	public function mark_as_publish($id, $published=1) {
		return $this->update_set($id, array('state' => $published));
	}
	
}
?>