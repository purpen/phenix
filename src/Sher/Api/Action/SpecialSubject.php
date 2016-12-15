<?php
/**
 * API 接口
 * 商品专题
 * @author tianshuai
 */
class Sher_Api_Action_SpecialSubject extends Sher_Api_Action_Base {

	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'index_subject_stick');
	
	/**
	 * 入口
	 */
	public function execute(){
		
		return $this->getlist();
	}
	
	/**
	 * 专题列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'category_id'=>1, 'products'=>1, 'banner_id'=>1, 'share_count'=>1,
			'cover_id'=>1, 'tags'=>1, 'summary'=>1, 'user_id'=>1, 'kind'=>1, 'stick'=>1, 'short_title'=>1,
			'state'=>1, 'view_count'=>1, 'comment_count'=>1, 'love_count'=>1, 'favorite_count'=>1, 'publish'=>1,
		);
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

        // 是否使用缓存
		$use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 0;
			
		$query   = array();
		$options = array();
        $result = array();
		
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
					$query['stick'] = $stick;
			}
		}
		
		$query['publish'] = 1;
		
		// 分页参数
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
		
		$options['some_fields'] = $some_fields;

		// 开启查询
		$service = Sher_Core_Service_SpecialSubject::instance();
		$result = $service->get_special_subject_list($query, $options);
		
		// 重建数据结果
    $data = array();
		for($i=0;$i<count($result['rows']);$i++){
      foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:null;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['aub']['view_url'];
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的专题不存在！', 3000);
		}
    $user_id = $this->current_user_id;
		
		$model = new Sher_Core_Model_SpecialSubject();
		$special_subject = $model->load((int)$id);

		if($special_subject['state']==0){
			return $this->api_json('访问的专题已禁用！', 3001);
		}

		if(empty($special_subject)) {
				return $this->api_json('访问的专题不存在！', 3002);
		}
    $some_fields = array(
      '_id', 'title', 'short_title', 'tags', 'tags_s', 'remark', 'kind', 'share_count',
      'cover_id', 'category_id', 'summary', 'product_ids', 'products', 'state',
      'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
    );
		$special_subject = $model->extended_model_row($special_subject);
		$special_subject['content'] = null;
		$product_arr = array();

    // 重建数据结果
    $data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($special_subject[$key]) ? $special_subject[$key] : null;
    }
    // 封面图url
    $data['cover_url'] = $special_subject['cover']['thumbnails']['aub']['view_url'];

    //验证是否收藏或喜欢
    $fav = new Sher_Core_Model_Favorite();
    $data['is_love'] = $fav->check_loved($user_id, $special_subject['_id'], 9) ? 1 : 0;
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_APPOINT){
			if(!empty($special_subject['product_ids'])){
			  $product_model = new Sher_Core_Model_Product();
			  foreach($special_subject['product_ids'] as $k=>$v){
          $product = $product_model->extend_load((int)$v);
          if(!empty($product)){
            $product_some_fields = array(
              '_id', 'title', 'short_title', 'advantage', 'sale_price', 'market_price',
              'cover_id', 'category_id', 'stage', 'summary',
              'snatched_time', 'inventory', 'can_saled', 'snatched',
              'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
              'comment_star','snatched_end_time', 'snatched_price', 'snatched_count',
            );

            // 重建数据结果
            $product_data = array();
            for($i=0;$i<count($product_some_fields);$i++){
              $key = $product_some_fields[$i];
              $product_data[$key] = isset($product[$key]) ? $product[$key] : null;
            }
            // 封面图url
            $assets = array();
            $asset_query = array('parent_id'=>$product['_id'], 'asset_type'=>11);
            $asset_options['page'] = 1;
            $asset_options['size'] = 1;
            $asset_service = Sher_Core_Service_Asset::instance();
            $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);
            
            $product_data['cover_url'] = !empty($asset_result['rows']) ? $asset_result['rows'][0]['thumbnails']['aub']['view_url'] : null;
            array_push($product_arr, $product_data);
          }
			  } // endfor
			} // endif empty
			$data['products'] = $product_arr;
		} // endif kind
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_CUSTOM){
			
			$data['content_view_url'] = sprintf('%s/view/special_subject_show?id=%d', Doggy_Config::$vars['app.url.api'], $special_subject['_id']);
		} // endif kind

    // 分享内容
    $data['share_view_url'] = sprintf("%s/special_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $data['_id']);
    $data['share_desc'] = Doggy_Dt_Filters_String::truncate(strip_tags($data['summary']), 80);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, (int)$id);

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     * 首页专题推荐
     */
    public function index_subject_stick(){
		
		$cate_query = array('domain'=>Sher_Core_Util_Constant::TYPE_SPECIAL_SUBJECT, 'is_open'=>Sher_Core_Model_Category::IS_OPENED, 'stick'=>1);
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1, 'sub_count'=>1, 'tag_id'=>1,
          'domain'=>1, 'is_open'=>1, 'total_count'=>1, 'reply_count'=>1, 'state'=>1, 'app_cover_url'=>1,
        );
		$cate_options = array('page'=>1, 'size'=>6, 'sort_field'=>'orby', 'some_fields'=>$some_fields);

        $special_subject_some_fields = array(
          '_id', 'title', 'cover_id', 'product_ids',
        );

        $product_some_fields = array(
          '_id', 'title', 'short_title', 'cover_id', 'sale_price',
        );
		
        $service = Sher_Core_Service_Category::instance();
        $result = $service->get_category_list($cate_query, $cate_options);

		$special_subject_model = new Sher_Core_Model_SpecialSubject();
        $product_model = new Sher_Core_Model_Product();

        $data = array();
        for($i=0;$i<count($result['rows']);$i++){
          foreach($cate_options['some_fields'] as $key=>$value){
            $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
          }

          $data[$i]['special_subject'] = null;

          $special_subject = $special_subject_model->find(array('category_id'=>$data[$i]['_id'], 'publish'=>1, 'stick'=>1), array('page'=>1,'size'=>1,'sort'=>array('updated_on'=>-1)));
          if(empty($special_subject)){
              $data[$i]['special_subject'] = null;
          }else{
              $special_subject = $special_subject[0];
              $special_subject = $special_subject_model->extended_model_row($special_subject);
              foreach($special_subject_some_fields as $v){
                $rebuild_special_subject[$v] = isset($special_subject[$v]) ? $special_subject[$v] : null;
              }

              $rebuild_special_subject['cover_url'] = isset($special_subject['cover']) ? $special_subject['cover']['thumbnails']['aub']['view_url'] : null;
              $data[$i]['special_subject'] = $rebuild_special_subject;
              $product_arr = array();
              $m = 0;
              foreach($special_subject['product_ids'] as $k=>$v){
                  $product = $product_model->extend_load((int)$v);
                  if(!empty($product)){
                    $m = $m + 1;
                    // 重建数据结果
                    $product_data = array();
                    for($j=0;$j<count($product_some_fields);$j++){
                      $key = $product_some_fields[$j];
                      $product_data[$key] = isset($product[$key]) ? $product[$key] : null;
                    }
                    
                    $product_data['cover_url'] = !empty($product['cover']) ? $product['cover']['thumbnails']['apc']['view_url'] : null;
                    array_push($product_arr, $product_data);
                    if($m>=3){
                        break;
                    }
                  }
              } // endfor
              $data[$i]['special_subject']['products'] = $product_arr;         
          } // endif special_subject

        }   // endfor $result[rows]

		$result['rows'] = $data;
        return $this->api_json('success', 0, $result);

    }
	
}

