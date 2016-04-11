<?php
/**
 * 产品专题
 * @author tianshuai
 */
class Sher_Wap_Action_SpecialSubject extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
    'size' => 8,

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view', 'ajax_load_list');

	/**
	 * 产品专题入口
	 */
	public function execute(){
		return $this->getlist();
	}

  /**
   * 列表
   */
  public function getlist(){

    return $this->to_html_page('wap/special_subject/list.html');   
  }

  /**
    *专题详情
    */
  public function view(){
    $id = (int)$this->stash['id'];
    $redirect_url = Doggy_Config::$vars['app.url.wap']."/special_subject";
    if(empty($id)){
      return $this->show_message_page('访问的专题不存在！', $redirect_url);
    }

    $user_id = $this->visitor->id;
	
	  $special_subject_model = new Sher_Core_Model_SpecialSubject();
	  $special_subject = $special_subject_model->load((int)$id);

    if(empty($special_subject)) {
      return $this->show_message_page('访问的专题不存在！', $redirect_url);
    }

    if($special_subject['state']==0){
      return $this->show_message_page('访问的专题已禁用！', $redirect_url);
    }

		$special_subject = $special_subject_model->extended_model_row($special_subject);
		$product_arr = array();
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_APPOINT){
			if(!empty($special_subject['product_ids'])){
			  $product_model = new Sher_Core_Model_Product();
			  foreach($special_subject['product_ids'] as $k=>$v){
          $product = $product_model->extend_load((int)$v);
          if(!empty($product)){
            array_push($product_arr, $product);
          }
			  } // endfor
			} // endif empty
		} // endif kind
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_CUSTOM){  // 用户自定义输出
      $this->stash['content'] = $special_subject['content'];
			$tpl = 'wap/special_subject/custom_view.html';
    }else{  // 产品列表
      $this->stash['products'] = $product_arr;
      $tpl = 'wap/special_subject/view.html';
    }
  	return $this->to_html_page($tpl);
  }

  /**
   * ajax加载列表
   */
  public function ajax_load_list(){    
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 6;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;

		$query   = array();
		$options = array();
		
		// 查询条件
		if($category_id){
			$query['category_id'] = (int)$category_id;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($stick){
			if($stick==-1){
					$query['stick'] = 0;
			}else{
					$query['stick'] = 1;
			}
		}

    $options['page'] = $page;
    $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:latest';
				break;
		}
        
		// 开启查询
		$service = Sher_Core_Service_SpecialSubject::instance();
		$result = $service->get_special_subject_list($query, $options);
		
		// 重建数据结果
    $data = array();
		for($i=0;$i<count($result['rows']);$i++){
      // 过滤用户表
      if(isset($result['rows'][$i]['user'])){
        $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
      }
		}
		$result['rows'] = $data;

    $data = array();
    $data['nex_page'] = $next_page;
    $data['results'] = $resultlist;

    $data['page'] = $page;
    $data['sort'] = $sort;
    $data['size'] = $size;
    $data['results'] = $result;
    
    return $this->ajax_json('success', false, '', $data);
  }
	
}
