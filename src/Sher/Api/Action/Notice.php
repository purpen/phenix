<?php
/**
 * 系统通知API接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_Notice extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
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
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'title'=>1, 'kind'=>1, 'published'=>1, 'evt'=>1, 'content'=>1, 'state'=>1, 'send_count'=>1, 'url'=>1,'created_on'=>1, 'updated_on'=>1,
		);
		
		// 查询条件
		$query['kind'] = Sher_Core_Model_Notice::KIND_SCENE;
		$query['published'] = 1;
		
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
      $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
      $result['rows'][$k]['content'] = htmlspecialchars(strip_tags($v['content']));
      $result['rows'][$k]['cover_url'] = null;
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
		}
		
		// 过滤多余属性
    $filter_fields  = array('state_label','__extend__');
    $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);

    //清空提醒数量
    if($page==1){
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->load($user_id);
      if($user && isset($user['counter']['fiu_notice_count']) && $user['counter']['fiu_notice_count']>0){
        $user_model->update_counter($user_id, 'fiu_notice_count');
      }
    }
			
		return $this->api_json('请求成功', 0, $result);
	}

}

