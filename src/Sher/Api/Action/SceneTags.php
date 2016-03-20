<?php
/**
 * 情景标签
 * @author tianshuai
 */
class Sher_Api_Action_SceneTags extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'scene_tags');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:100;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		$status = isset($this->stash['status']) ? (int)$this->stash['status'] : -1;
		$parent_id = isset($this->stash['parent_id']) ? (int)$this->stash['parent_id'] : 0;
		$title_cn = isset($this->stash['title_cn']) ? (int)$this->stash['title_cn'] : null;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;

		$some_fields = array(
      '_id'=>1, 'title_cn'=>1, 'title_en'=>1, 'likename'=>1, 'parent_id'=>1, 'left_ref'=>1,
			'right_ref'=>1, 'cover_id'=>1, 'used_count'=>1, 'type'=>1, 'status'=>1, 'stick'=>1, 
		);
		
		$query   = array();
		$options = array();

    if($type){
      if($type==-1){
        $query['type'] = 0;
      }else{
        $query['type'] = (int)$type;
      }
    }

    if($title_cn){
      $query['title_cn'] = $title_cn;
    }
		
    // 查询条件
    if($parent_id){
      $query['parent_id'] = (int)$parent_id;
    }

    if($stick){
      if($stick==-1){
        $query['stick'] = 0;         
      }else{
        $query['stick'] = 1;         
      }
    }

    if ($status) {
      if((int)$status==-1){
        $query['status'] = 0;
      }else{
        $query['status'] = 1;         
      }
    }

    if($user_id){
      $query['user_id'] = (int)$user_id;         
    }
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
    $service = Sher_Core_Service_SceneTags::instance();
    $result = $service->get_scene_tags_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 情景标签列表,获取下一级分类
	 */
	public function scene_tags(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:100;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : -1;
		$status = isset($this->stash['status']) ? (int)$this->stash['status'] : 1;
		$parent_id = isset($this->stash['parent_id']) ? (int)$this->stash['parent_id'] : 0;
		$title_cn = isset($this->stash['title_cn']) ? (int)$this->stash['title_cn'] : null;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;

		$some_fields = array(
      '_id'=>1, 'title_cn'=>1, 'title_en'=>1, 'likename'=>1, 'parent_id'=>1, 'left_ref'=>1,
			'right_ref'=>1, 'cover_id'=>1, 'used_count'=>1, 'type'=>1, 'status'=>1, 'stick'=>1, 
		);
		
		$query   = array();
		$options = array();

    if($type){
      if($type==-1){
        $query['type'] = 0;
      }else{
        $query['type'] = (int)$type;
      }
    }
		
    // 查询条件
    if($parent_id){
      $query['parent_id'] = (int)$parent_id;
    }

    if ($status) {
      if((int)$status==-1){
        $query['status'] = 0;
      }else{
        $query['status'] = 1;         
      }
    }
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
    $service = Sher_Core_Service_SceneTags::instance();
    $result = $service->get_scene_tags_list($query, $options);
		
		// 重建数据结果
		$data = array();
    $scene_tags_model = new Sher_Core_Model_SceneTags();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}
      $id = $data[$i]['_id'];
      $type = $data[$i]['type'];
      $sub_tags = $scene_tags_model->find(array('parent_id'=>$id, 'type'=>$type, 'stick'=>1, 'status'=>1));
      $data[$i]['sub_tags'] = $sub_tags;
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	

}

