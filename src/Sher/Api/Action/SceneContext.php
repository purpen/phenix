<?php
/**
 * 语境管理
 * @author tianshuai
 */
class Sher_Api_Action_SceneContext extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view');

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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'des'=>1, 'category_id'=>1, 'category', 'tags'=>1, 'used_count'=>1, 'status'=>1, 'stick'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();

        if($category_id){
            $query['category_id'] = $category_id;
        }
		
		if($stick){
			if($stick == -1){
				$query['stick'] = 0;
            }else{
 				$query['stick'] = 1;           
            }
		}
		
		// 状态
		$query['status'] = 1;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:update';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SceneContext::instance();
        $result = $service->get_scene_context_list($query, $options);
		
		// 重建数据结果
        foreach($result['rows'] as $k => $v){
            $category = array();
            $category['_id'] = $result['rows'][$k]['category']['_id'];
            $category['title'] = $result['rows'][$k]['category']['title'];
            $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
            $result['rows'][$k]['category'] = $category;
		}

		// 过滤多余属性
        $filter_fields  = array('__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/*
	 * 详情
	 */
	public function view(){

	}
}

