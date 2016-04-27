<?php
/**
 * 情景标签
 * @author tianshuai
 */
class Sher_Api_Action_SceneTags extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'hotlist');

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
		
		// 请求参数
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5000;
		
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
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
		  $query['type'] = (int)$type;
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
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'left_ref';
				break;
			case 1:
				$options['sort_field'] = 'used_count';
				break;
			case 2:
				$options['sort_field'] = 'scene_count';
				break;
			case 3:
				$options['sort_field'] = 'sight_count';
				break;
			case 4:
				$options['sort_field'] = 'context_count';
				break;
			case 5:
				$options['sort_field'] = 'product_count';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
		$service = Sher_Core_Service_SceneTags::instance();
		$result = $service->get_scene_tags_list($query, $options);
		
		// 过滤多余属性
        $filter_fields  = array('likename', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		// 重建数据结果
		$result = Sher_Core_Model_SceneTags::handle($result);
		$result = Sher_Core_Helper_Util::arrayToTree($result['rows'],'_id','parent_id','children');
		/*
		foreach($result as $k => $v){
			if($v['parent_id'] == 0){
				$result = $result[$k]['children'];
			}
		}
		*/
		
		print_r($result);exit;
		return $this->api_json('请求成功', 0, $result);
	}

}