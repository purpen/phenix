<?php
/**
 * 社区主题API接口
 * @author purpen
 */
class Sher_Api_Action_Topic extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {
	
	public $stash = array(

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view', 'category', 'replis', 'submit');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 主题列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? $this->stash['category_id'] : 0;
		$user_id   = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		$target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		$try_id = isset($this->stash['try_id']) ? $this->stash['try_id'] : 0;
		
		$query   = array();
		$options = array();

    //显示的字段
    $options['some_fields'] = array(
      '_id'=> 1, 'title'=>1, 'category_id'=>1, 'target_id'=>1, 'cover_id'=>1, 'asset'=>1, 'parent_id'=>1, 'view_count'=>1, 'stick'=>1,
      'deleted'=>1, 'published'=>1, 'user_id'=>1, 'comment_count'=>1, 'created_on'=>1,
    );
		
		// 查询条件
		if($category_id){
			$query['category_id'] = (int)$category_id;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($target_id){
			$query['target_id'] = (int)$target_id;
		}
		if($try_id){
			$query['try_id'] = (int)$try_id;
		}
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;
		$options['sort_field'] = 'latest';

		// 开启查询
    $service = Sher_Core_Service_Topic::instance();
    $result = $service->get_topic_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
				$data[$i][$key] = $result['rows'][$i][$key];
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['medium']['view_url'];
			// 用户信息
			$data[$i]['username'] = $result['rows'][$i]['user']['nickname'];
			$data[$i]['small_avatar_url'] = $result['rows'][$i]['user']['small_avatar_url'];
      $data[$i]['content_view_url'] = sprintf('%s/view/topic_show?id=%d&current_user_id=%d', Doggy_Config::$vars['app.domain.base'], $result['rows'][$i]['_id'], $this->current_user_id);
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 主题详情
	 */
	public function view(){
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
    $user_id = $this->current_user_id;
		if(empty($id)){
			return $this->api_json('访问的主题不存在！', 3000);
		}
		
		// 是否允许编辑
		$editable = false;
		$result = array();
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = $model->extend_load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->api_json('访问的主题不存在或已被删除！', 3001);
		}
		
		// 增加pv++
		$inc_ran = rand(1, 6);
		$model->increase_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
    if ($this->current_user_id == $topic['user_id']){
      $editable = true;
    }
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $result['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$result['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $topic['is_favorite'] = $fav->check_favorite($this->current_user_id, $topic['_id'], 2) ? 1 : 0;
    $topic['is_love'] = $fav->check_loved($this->current_user_id, $topic['_id'], 2) ? 1 : 0;
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$topic['fid']);
		
    $topic['content_view_url'] = sprintf('%s/view/topic_show?id=%d&current_user_id=%d', Doggy_Config::$vars['app.domain.base'], $topic['_id'], $user_id);
    $topic['description'] = null;
		$result['topic'] = &$topic;
		$result['parent_category'] = $parent_category;
		$result['editable'] = $editable;
		
		// 评论的链接URL
		$result['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		// 判定是否产品话题
		if (isset($topic['target_id']) && !empty($topic['target_id'])){
			$product = new Sher_Core_Model_Product();
			$result['product'] = & $product->extend_load($topic['target_id']);
		}
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 提交话题
	 */
	public function submit(){
		$user_id = $this->current_user_id;
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
    $data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		$data['asset'] = isset($this->stash['asset'])?$this->stash['asset']:array();
		
		if(empty($data['title']) || empty($data['description']) || empty($data['category_id'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$topic = $model->get_data();
				
				$id = (string)$topic['_id'];
				
				if($ok){
					// 更新用户主题数量
					$user = new Sher_Core_Model_User();
					$user->inc_counter('topic_count', $user_id);
					unset($user);
				}
			}else{
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			// 上传成功后，更新所属的附件
			/*
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}*/			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api主题保存失败：".$e->getMessage());
			return $this->api_json('主题保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}
	
	/**
	 * 分类
	 */
	public function category(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$query   = array();
		$options = array();
		
		$query['domain'] = Sher_Core_Util_Constant::TYPE_TOPIC;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
		
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'orby';
		
        $service = Sher_Core_Service_Category::instance();
        $result = $service->get_category_list($query, $options);

      // 重建数据结果
      $data = array();
      for($i=0;$i<count($result['rows']);$i++){
        //特殊字符转换$amp;
        $result['rows'][$i]['title'] = htmlspecialchars_decode($result['rows'][$i]['title']);
      }
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 主题回复列表
	 */
	public function replis(){
		$type = Sher_Core_Model_Comment::TYPE_TOPIC;
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		// 请求参数
        $user_id = $this->current_user_id;
        $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		if(empty($target_id)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($user_id){
            $query['user_id'] = (int) $user_id;
        }
		if($target_id){
			$query['target_id'] = (string)$target_id;
		}
		if($type){
			$query['type'] = (int)$type;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'earliest';
		
		  // 开启查询
        $service = Sher_Core_Service_Comment::instance();
        $result = $service->get_comment_list($query, $options);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 回复
	 */
	public function ajax_comment(){
		$data = array();
		$result = array();
		
		// 验证数据
		$data['target_id'] = $this->stash['target_id'];
		$data['content'] = $this->stash['content'];
		if(empty($data['target_id']) || empty($data['content'])){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		$data['user_id'] = $this->current_user_id;
		$data['type'] = Sher_Core_Model_Comment::TYPE_TOPIC;
		
		try{
			// 保存数据
			$model = new Sher_Core_Model_Comment();
			$ok = $model->apply_and_save($data);
			if($ok){
				$comment_id = $model->id;
				$result['comment'] = &$model->extend_load($comment_id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('操作成功', 0, $result);
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if(!$model->check_favorite($this->current_user_id, $id, $type)){
				$fav_info = array('type' => $type);
				$ok = $model->add_favorite($this->current_user_id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}

	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
    $id = $this->stash['id'];
    $user_id = $this->current_user_id;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		if(empty($user_id)){
			return $this->api_json('缺少用户ID！', 3001);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if($model->check_favorite($user_id, $id, $type)){
				$ok = $model->remove_favorite($user_id, $id, $type);
        if(!$ok){
     			return $this->api_json('操作失败', 3002);   
        }
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}
	
	/**
	 * 点赞
	 */
	public function ajax_love(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_loved($this->current_user_id, $id, $type)) {
				$fav_info = array('type' => $type);
				$ok = $model->add_love($this->current_user_id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试:'.$e->getMessage(), 3001);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}

	/**
	 * 取消点赞
	 */
	public function ajax_cancel_love(){
    $id = $this->stash['id'];
    $user_id = $this->current_user_id;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if ($model->check_loved($user_id, $id, $type)) {
				$ok = $model->cancel_love($user_id, $id, $type);
        if(!$ok){
 			    return $this->api_json('操作失败', 3002);       
        }
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试:'.$e->getMessage(), 3003);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}
	
	/**
	 * 计算总数
	 */
	protected function remath_count($id, $field='favorite_count'){
		$count = 0;
		
		$model = new Sher_Core_Model_Topic();
		$result = $model->load((int)$id);
		
		if(!empty($result)){
			$count = $result[$field];
		}
		
		return $count;
	}
	
}
?>
