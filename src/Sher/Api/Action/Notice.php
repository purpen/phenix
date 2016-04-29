<?php
/**
 * 系统通知API接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_Notice extends Sher_Api_Action_Base {
	
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
		
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		$user_id = $this->current_user_id;
		//$user_id = 10;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'title'=>1, 'content'=>1, 'state'=>1, 'send_count'=>1, 'url'=>1,'created_on'=>1, 'updated_on'=>1, 'created_at'=>1,
		);
		
		// 查询条件
		
		$query['user_id'] = $user_id;
		$query['published'] = 1;
		$query['kind'] = Sher_Core_Model_Notice::KIND_SCENE;
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		// 开启查询
		$service = Sher_Core_Service_Notice::instance();
		$result = $service->get_notice_list($query,$options);
		
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($v['created_on']);
			$result['rows'][$k]['content'] = htmlspecialchars($v['content']);
		}
		
		// 过滤多余属性
        $filter_fields  = array('state_label','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
			
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}

}

