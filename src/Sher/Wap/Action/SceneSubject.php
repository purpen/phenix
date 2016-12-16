<?php
/**
 * 情境专题
 * @author tianshuai
 */
class Sher_Wap_Action_SceneSubject extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
        'size' => 8,

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view', 'ajax_fetch_more');

	/**
	 * 情境专题入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
      *详情
    */
    public function view(){
        $this->set_target_css_state('page_choice');
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        if(empty($id)){
          return $this->show_message_page('访问的专题不存在！', $redirect_url);
        }
        $user_id = $this->visitor->id;

		$model = new Sher_Core_Model_SceneSubject();
		$scene_subject = $model->extend_load($id);

		if(empty($scene_subject)) {
            return $this->show_message_page('访问的专题不存在！', $redirect_url);
		}

		if($scene_subject['publish']==0){
            return $this->show_message_page('访问的专题未发布！', $redirect_url);
		}

		if($scene_subject['status']==0){
            return $this->show_message_page('访问的专题已禁用！', $redirect_url);
		}

        // 新品
        $product = null;
        if(!empty($scene_subject['product_id'])){
            $product_model = new Sher_Core_Model_Product();
            $product = $product_model->extend_load((int)$scene_subject['product_id']);
        }
        $scene_subject['product'] = $product;

        // 产品
        $product_arr = array();
        if(!empty($scene_subject['product_ids'])){
            $product_model = new Sher_Core_Model_Product();
            for($i=0;$i<count($scene_subject['product_ids']);$i++){
                $product = $product_model->extend_load($scene_subject['product_ids'][$i]);
                if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                $row = array(
                    '_id' => $product['_id'],
                    'title' => $product['short_title'],
                    'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                    'cover_url' => $product['cover']['thumbnails']['apc']['view_url'],
                    'summary' => $product['summary'],
                    'market_price' => $product['market_price'],
                    'sale_price' => $product['sale_price'],
                    'wap_view_url' => $product['wap_view_url'],
                );
                array_push($product_arr, $row);
            }
        }
        $scene_subject['products'] = $product_arr;

        $this->stash['scene_subject'] = $scene_subject;
        $this->stash['content'] = $scene_subject['content'];

        if($scene_subject['type']==1){  // 文章
            $tpl = 'wap/scene_subject/artile.html';
        }elseif($scene_subject['type']==3){ // 促销
            $tpl = 'wap/scene_subject/hot.html';       
        }elseif($scene_subject['type']==4){ // 新品
            $tpl = 'wap/scene_subject/new.html';
        }elseif($scene_subject['type']==5){ // 好货(与促销相同)
            if(isset($scene_subject['mode'])){
                if($scene_subject['mode']==1){
                    $tpl = 'wap/scene_subject/hot.html';       
                }elseif($scene_subject['mode']==2){
                    $tpl = 'wap/scene_subject/hot_b.html';
                }
            }else{
                $tpl = 'wap/scene_subject/hot.html';            
            }
        }else{
            $tpl = 'wap/scene_subject/artile.html';       
        }

        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

  	    return $this->to_html_page($tpl);
    }


  /**
   * 自动加载获取
   */
  public function ajax_fetch_more(){
        
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
        // 是否使用缓存
        $use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 0;
        
        $query = array();
        $query['publish'] = 1;

        if($user_id){
            $query['user_id'] = $user_id;
        }

        // 类型
        if($type){
            $query['type'] = $type;
        }
        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
        switch ((int)$sort) {
          case 0:
            $options['sort_field'] = 'latest';
            break;
          case 1:
            $options['sort_field'] = 'stick';
            break;
          case 2:
            $options['sort_field'] = 'fine';
            break;
          case 3:
            $options['sort_field'] = 'view';
            break;
        }

        //限制输出字段
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'short_title'=>1,'kind'=>1, 'type'=>1, 'summary'=>1, 'banner'=>1,
          'fine'=>1, 'fine_on'=>1, 'stick'=>1, 'stick_on'=>1, 'category_id'=>1, 'created_on'=>1,
          'publish'=>1, 'status'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
          'updated_on'=>1, 'love_count'=>1, 'deleted'=>1,'publish'=>1, 'tags'=>1, 'sight_ids'=>1,
          'evt'=>1, 'product_ids'=>1, 'product_id'=>1, 'attend_count'=>1, 'share_count'=>1,
          'begin_time'=>1, 'end_time'=>1, 'extra_tag'=>1,
          
        );
        $options['some_fields'] = $some_fields;
        
        $service = Sher_Core_Service_SceneSubject::instance();
        $result = $service->get_scene_subject_list($query,$options);

		$product_model = new Sher_Core_Model_Product();
        $next_page = 'no';
        if(isset($result['next_page'])){
            if((int)$result['next_page'] > $page){
                $next_page = (int)$result['next_page'];
            }
        }
        
        $max = count($result['rows']);

        // 重建数据结果
        $data = array();
        for($i=0;$i<$max;$i++){
            $obj = $result['rows'][$i];

            foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($obj[$key]) ? $obj[$key] : null;
			}
			// 封面图url
			$data[$i]['cover_url'] = $obj['cover']['thumbnails']['aub']['view_url'];
			// Banner url
			//$data[$i]['banner_url'] = $obj['banner']['thumbnails']['aub']['view_url'];

            $data[$i]['begin_time_at'] = date('m/d', $data[$i]['begin_time']);
            $data[$i]['end_time_at'] = date('m/d', $data[$i]['end_time']);

            $data[$i]['wap_view_url'] = sprintf("%s/scene_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $data[$i]['_id']);

            $data[$i]['is_extra_tag'] = false;
            if(isset($obj['extra_tag']) && !empty($obj['extra_tag'])){
                $data[$i]['is_extra_tag'] = true;
            }

            // 产品
            $product_arr = array();
            if(!empty($data[$i]['product_ids'])){
                for($j=0;$j<count($data[$i]['product_ids']);$j++){
                    $product = $product_model->extend_load((int)$data[$i]['product_ids'][$j]);
                    if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                    $row = array(
                        '_id' => $product['_id'],
                        'title' => $product['short_title'],
                        'cover_url' => $product['cover']['thumbnails']['apc']['view_url'],
                        'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                        'summary' => $product['summary'],
                        'sale_price' => $product['sale_price'],
                        'wap_view_url' => $product['wap_view_url'],
                    );
                    array_push($product_arr, $row);
                }
            }
            $data[$i]['products'] = $product_arr;

        } //end for

        $result['rows'] = $data;
        $result['nex_page'] = $next_page;

        $result['type'] = $type;
        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);
    }

	
}
