<?php
/**
 * 社区主题API接口
 * @author purpen
 */
class Sher_Api_Action_Topic extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'size' => 10,
		'id' => 0,
	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view', 'category', 'replis');
	
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
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? $this->stash['category_id'] : 0;
		$user_id   = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		$target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		$try_id = isset($this->stash['try_id']) ? $this->stash['try_id'] : 0;
		
		$query   = array();
		$options = array();
		
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
		
		return $this->ajax_json('请求成功', false, '/', $result);
	}
	
	/**
	 * 主题详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->ajax_json('访问的主题不存在！', true, $redirect_url);
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
			return $this->ajax_json('访问的主题不存在或已被删除！', true, $redirect_url);
		}
		
		// 增加pv++
		$inc_ran = rand(1, 6);
		$model->increase_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
		if ($this->visitor->id){
			if ($this->visitor->id == $topic['user_id'] || $this->visitor->can_admin){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $result['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$result['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$topic['fid']);
		
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
		
		return $this->ajax_json('请求成功', false, '/', $result);
	}
	
	/**
	 * 分类
	 */
	public function category(){
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		$query   = array();
		$options = array();
		
		$query['domain'] = Sher_Core_Util_Constant::TYPE_TOPIC;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
		
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'orby';
		
        $service = Sher_Core_Service_Category::instance();
        $result = $service->get_category_list($query, $options);
		
		return $this->ajax_json('请求成功', false, '/', $result);
	}
	
	/**
	 * 主题回复列表
	 */
	public function replis(){
		$type = Sher_Core_Model_Comment::TYPE_TOPIC;
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		// 请求参数
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
        $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		
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
		
		return $this->ajax_json('请求成功', false, '/', $result);
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
		$data['star'] = $this->stash['star'];
		if(empty($data['target_id']) || empty($data['content'])){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$data['user_id'] = $this->visitor->id;
		$data['type'] = Sher_Core_Model_Favorite::TYPE_TOPIC;
		
		// 保存数据
		$model = new Sher_Core_Model_Comment();
		$ok = $model->apply_and_save($data);
		if($ok){
			$comment_id = $model->id;
			$result['comment'] = &$model->extend_load($comment_id);
		}
		
		return $this->ajax_json('操作成功', false, '/', $result);
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if(!$model->check_favorite($this->visitor->id, $id, $type)){
				$fav_info = array('type' => $type);
				$ok = $model->add_favorite($this->visitor->id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, 'favorite_count');
		
		return $this->ajax_json('操作成功', false, '/', array('favorite_count'=>$favorite_count));
	}
	
	/**
	 * 点赞
	 */
	public function ajax_love(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$type = Sher_Core_Model_Favorite::TYPE_TOPIC;
			
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_loved($this->visitor->id, $id, $type)) {
				$fav_info = array('type' => $type);
				$ok = $model->add_love($this->visitor->id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, 'love_count');
		
		return $this->ajax_json('操作成功', false, '/', array('love_count'=>$love_count));
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