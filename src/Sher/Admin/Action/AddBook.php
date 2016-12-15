<?php
/**
 * 地址管理
 * @author tianshuai
 */
class Sher_Admin_Action_AddBook extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'kind' => 0,
	);
	
	public function _init() {
		// 判断左栏类型
		$this->stash['show_type'] = "sales";
		$this->set_target_css_state('page_add_book');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
        $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : '';

        $query = array();
        $options = array();

        if($user_id){
            $query['user_id'] = $user_id;
        }

        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

        //限制输出字段
		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'county_id'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1, 'created_on'=>1,
		);
        $options['some_fields'] = $some_fields;

        $user_model = new Sher_Core_Model_User();
        
		// 开启查询
        $service = Sher_Core_Service_DeliveryAddress::instance();
        $result = $service->get_address_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : '';
			}
            $data[$i]['_id'] = (string)$data[$i]['_id'];
            $data[$i]['is_default'] = empty($data[$i]['is_default']) ? false : true;

            $user = $user_model->extend_load($data[$i]['user_id']);
            $data[$i]['user'] = $user;
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/add_book?user_id=%d&page=#p#', $user_id);
		
        $result['rows'] = $data;
        $this->stash['delivery_addresses'] = $result;
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/add_book/list.html');
	}

	/**
	 * old列表
	 */
	public function get_old_list() {

        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : '';
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/add_book?user_id=%d&page=#p#', $user_id);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/add_book/old_list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:null;
		if(empty($id)){
			return $this->ajax_notification('地址不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			
			foreach($ids as $id){
				$add_book = $model->load($id);
				
				if (!empty($add_book)){
					//逻辑删除
					$model->remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}

