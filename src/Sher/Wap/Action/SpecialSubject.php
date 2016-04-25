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

		// 增加pv++
		$special_subject_model->inc_counter('view_count', 1, $id);

		$special_subject = $special_subject_model->extended_model_row($special_subject);
		$product_arr = array();

		//评论参数
		$comment_options = array(
		  'comment_target_id' =>  $special_subject['_id'],
		  'comment_target_user_id' => $special_subject['user_id'],
		  'comment_type'  =>  9,
		  //'comment_pager' =>  Sher_Core_Helper_Url::topic_view_url($id, '#p#'),
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_APPOINT){
			if(!empty($special_subject['product_ids'])){

			  $product_model = new Sher_Core_Model_Product();
			  foreach($special_subject['product_ids'] as $k=>$v){
          $product = $product_model->extend_load((int)$v);

          // 封面图url
          $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>11);
          $asset_options['page'] = 1;
          $asset_options['size'] = 1;
          $asset_service = Sher_Core_Service_Asset::instance();
          $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);
          $product['cover_url'] = !empty($asset_result['rows']) ? $asset_result['rows'][0]['thumbnails']['aub']['view_url'] : null;
          if(!empty($product)){
            array_push($product_arr, $product);
          }
			  } // endfor
			} // endif empty
		} // endif kind
		
    $this->stash['special_subject'] = $special_subject;
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
		
		$query['publish'] = 1;
		
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

    //限制输出字段
    $some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'user_id'=>1, 'cover_id'=>1, 'cover'=>1,
      'product_ids'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'content'=>1,
      'summary'=>1, 'last_reply_time'=>1, 'kind'=>1, 'comment_count'=>1, 'view_count'=>1,
      'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'tags'=>1, 'wap_view_url'=>1,
      'view_count'=>1, 'state'=>1,
    );
    $options['some_fields'] = $some_fields;
        
		// 开启查询
		$service = Sher_Core_Service_SpecialSubject::instance();
		$result = $service->get_special_subject_list($query, $options);
		$next_page = 'no';
		// 重建数据结果
    $data = array();
		for($i=0;$i<count($result['rows']);$i++){
      // 过滤用户表
      if(isset($result['rows'][$i]['user'])){
        $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
      }
		}

    $data = array();
    $data['nex_page'] = $next_page;
    $data['results'] = $result;

    $data['page'] = $page;
    $data['sort'] = $sort;
    $data['size'] = $size;
    
    return $this->ajax_json('success', false, '', $data);
  }


  /**
   * 评论参数
   */
  protected function _comment_param($options){
        $this->stash['comment_target_id'] = $options['comment_target_id'];
        $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
        $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }
	
}
