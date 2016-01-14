<?php
/**
 * 社区评论API接口
 * @author tianshuai
 */
class Sher_Api_Action_Comment extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist');
	
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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		// 请求参数
		$user_id   = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

		if(empty($target_id)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		$query   = array();
		$options = array();

    //显示的字段
    $options['some_fields'] = array(
      '_id'=>1, 'user_id'=>1, 'content'=>1, 'star'=>1, 'target_id'=>1, 'target_user_id'=>1, 'sku_id'=>1,
      'deleted'=>1, 'reply_user_id'=>1, 'floor'=>1, 'type'=>1, 'sub_type'=>1, 'user'=>1, 'target_user'=>1,
      'love_count'=>1, 'invented_love_count'=>1, 'is_reply'=>1, 'reply_id'=>1, 'created_on'=>1, 'updated_on'=>1,
    );
		
		// 查询条件
		if ($target_id) {
			$query['target_id'] = (string)$target_id;
		}
		if ($type) {
			$query['type'] = $type;
		}
    if ($user_id) {
        $query['user_id'] = (int) $user_id;
    }

		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'earliest';
				break;
			case 1:
				$options['sort_field'] = 'latest';
				break;
			case 2:
				$options['sort_field'] = 'hotest';
				break;
		}

		// 开启查询
    $service = Sher_Core_Service_Comment::instance();
    $result = $service->get_comment_list($query,$options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
      foreach($options['some_fields'] as $key=>$val){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}
      $data[$i]['_id'] = (string)$data[$i]['_id'];
      if($data[$i]['user']){
        $data[$i]['user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['user']);
      }
      if($data[$i]['target_user']){
        $data[$i]['target_user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['target_user']);
      }
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	

	/**
	 * 回复
	 */
	public function ajax_comment(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
 		  return $this->api_json('请先登录', 3000);   
    }
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
		
		// 验证数据
		$data = array();
		$data['target_id'] = $this->stash['target_id'];
		$data['content'] = $this->stash['content'];
    
		if(empty($data['target_id']) || empty($data['content'])){
			return $this->api_json('获取数据错误,请重新提交', 3001);
		}
		
		$data['user_id'] = $user_id;
		$data['type'] = $type;
		
		try{
			// 保存数据
			$model = new Sher_Core_Model_Comment();
			$ok = $model->apply_and_save($data);
			if($ok){
				$comment_id = $model->id;
				$comment = &$model->extend_load($comment_id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('操作成功', 0, $comment);
	}

	
}

