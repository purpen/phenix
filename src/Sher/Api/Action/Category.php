<?php
/**
 * 分类API接口
 * @author tianshuai
 */
class Sher_Api_Action_Category extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 分类
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		$domain = isset($this->stash['domain'])?(int)$this->stash['domain']:1;
		
		$query   = array();
		$options = array();
		
		$query['domain'] = $domain;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
		
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'orby';

        $some_fields = array(
          '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1,
          'domain'=>1, 'is_open'=>1, 'total_count'=>1, 'reply_count'=>1, 'state'=>1, 'app_cover_url'=>1,
        );
		
        $options['some_fields'] = $some_fields;

        $service = Sher_Core_Service_Category::instance();
        $result = $service->get_category_list($query, $options);

        // 过滤多余属性
        $filter_fields = array('view_url', 'state', 'is_open', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);

		return $this->api_json('请求成功', 0, $result);
	}

	
}

