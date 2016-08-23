<?php
/**
 * API 接口
 * 情境专题 文章、活动、促销、最新
 * @author tianshuai
 */
class Sher_Api_Action_SceneSubject extends Sher_Api_Action_Base {

	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'record_share_count');
	
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
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['fine']) ? (int)$this->stash['fine'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
			
		$query   = array();
		$options = array();

		$some_fields = array(
			'_id'=>1, 'title'=>1, 'short_title'=>1, 'category_id'=>1, 'publish'=>1, 'type'=>1,
            'cover_id'=>1, 'banner_id'=>1, 'tags'=>1, 'summary'=>1, 'user_id'=>1, 'evt'=>1, 'kind'=>1, 'stick'=>1, 'fine'=>1, 
            'stick_on'=>1, 'fine_on'=>1, 'begin_time'=>1, 'end_time'=>1, 'product_ids'=>1,
			'status'=>1, 'view_count'=>1, 'comment_count'=>1, 'love_count'=>1, 'favorite_count'=>1, 'attend_count'=>1, 'share_count'=>1,
		);
		
		$query['publish'] = 1;
        $query['status'] = 1;

		// 查询条件
		if($user_id){
			$query['user_id'] = $user_id;
		}

		if($type){
			$query['type'] = $type;
		}
		
		if($stick){
			if($stick==-1){
				$query['stick'] = 0;
			}else{
				$query['stick'] = 1;
			}
		}

		if($fine){
			if($fine==-1){
				$query['fine'] = 0;
			}else{
				$query['fine'] = 1;
			}
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick';
				break;
			case 2:
				$options['sort_field'] = 'fine';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
		$service = Sher_Core_Service_SceneSubject::instance();
		$result = $service->get_scene_subject_list($query, $options);

        $product_model = new Sher_Core_Model_SceneProduct();
		
		// 重建数据结果
        $data = array();
		for($i=0;$i<count($result['rows']);$i++){
            foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['apc']['view_url'];
			// Banner url
			//$data[$i]['banner_url'] = $result['rows'][$i]['banner']['thumbnails']['aub']['view_url'];

            $data[$i]['begin_time_at'] = date('Y-m-d', $data[$i]['begin_time']);
            $data[$i]['end_time_at'] = date('Y-m-d', $data[$i]['end_time']);

            // 产品
            $product_arr = array();
            if(!empty($data[$i]['product_ids'])){
                for($j=0;$j<count($data[$i]['product_ids']);$j++){
                    $product = $product_model->extend_load((int)$data[$i]['product_ids'][$j]);
                    if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                    $row = array(
                        '_id' => $product['_id'],
                        'title' => $product['short_title'],
                        'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                        'summary' => $product['summary'],
                        'sale_price' => $product['sale_price'],
                    );
                    array_push($product_arr, $row);
                }
            }
            $data[$i]['products'] = $product_arr;

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('访问的专题不存在！', 3000);
		}
        $current_user_id = $this->current_user_id;

        $some_fields = array(
          '_id', 'title', 'short_title', 'tags', 'tags_s', 'kind', 'evt', 'attend_count', 'type',
          'cover_id', 'category_id', 'summary', 'status', 'publish', 'user_id', 'sight_ids', 'product_ids',
          'stick', 'fine', 'love_count', 'favorite_count', 'view_count', 'comment_count', 'share_count',
          'begin_time', 'end_time', 'product_id',
        );
		
		$model = new Sher_Core_Model_SceneSubject();
		$scene_subject = $model->load($id);

		if(empty($scene_subject)) {
				return $this->api_json('访问的专题不存在！', 3001);
		}

		if($scene_subject['publish']==0){
			return $this->api_json('访问的专题未发布！', 3002);
		}

		if($scene_subject['status']==0){
			return $this->api_json('访问的专题已禁用！', 3003);
		}

        $follow_model = new Sher_Core_Model_Follow();

		$scene_subject = $model->extended_model_row($scene_subject);
		$scene_subject['content'] = null;


        // 重建数据结果
        $data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($scene_subject[$key]) ? $scene_subject[$key] : null;
        }
        // 封面图url
        $data['cover_url'] = $scene_subject['cover']['thumbnails']['aub']['view_url'];

        $product = null;
        if(!empty($data['product_id'])){
            $product_model = new Sher_Core_Model_Product();
            $row = $product_model->load((int)$data['product_id']);
            if(!empty($row)){
                $product['_id'] = $row['_id'];
                $product['is_favorite'] = 0;
                //验证是否收藏或喜欢
                if(!empty($current_user_id)){
                    $favorite_model = new Sher_Core_Model_Favorite();
                    $product['is_favorite'] = $favorite_model->check_favorite($current_user_id, $row['_id'], 1) ? 1 : 0;
                }

            }
        }
        $data['product'] = $product;

        // 情境
        $sight_arr = array();
        if(!empty($data['sight_ids'])){
            $sight_model = new Sher_Core_Model_SceneSight();
            for($i=0;$i<count($data['sight_ids']);$i++){
                $sight = $sight_model->extend_load($data['sight_ids'][$i]);
                if(empty($sight) || $sight['deleted']==1 || $sight['is_check']==0) continue;
                $row = array(
                    '_id' => $sight['_id'],
                    'title' => $sight['title'],
                    'cover_url' => $sight['cover']['thumbnails']['huge']['view_url'],
                    'created_at' => Sher_Core_Helper_Util::relative_datetime($sight['created_on']),
                );

                $user = array(
                    '_id' => $sight['user']['_id'],
                    'nickname' => $sight['user']['nickname'],
                );

                // 当前用户是否关注创建者
                $user['is_follow'] = 0;
                if($current_user_id){
                    if($follow_model->has_exist_ship($current_user_id, $user['_id'])){
						$user['is_follow'] = 1;
					}
                }
                $row['user'] = $user;
                
                array_push($sight_arr, $row);
            }
        
        }
        $data['sights'] = $sight_arr;

        // 产品
        $product_arr = array();
        if(!empty($data['product_ids'])){
            $scene_product_model = new Sher_Core_Model_SceneProduct();
            for($i=0;$i<count($data['product_ids']);$i++){
                $product = $scene_product_model->extend_load($data['product_ids'][$i]);
                if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                $row = array(
                    '_id' => $product['_id'],
                    'title' => $product['short_title'],
                    'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                    'summary' => $product['summary'],
                    'market_price' => $product['market_price'],
                    'sale_price' => $product['sale_price'],
                );
                array_push($product_arr, $row);
            }
        }
        $data['products'] = $product_arr;

        $data['begin_time_at'] = date('Y-m-d', $data['begin_time']);
        $data['end_time_at'] = date('Y-m-d', $data['end_time']);

        //验证是否收藏或喜欢
        $data['is_love'] = 0;
        if(!empty($current_user_id)){
            $favorite_model = new Sher_Core_Model_Favorite();
            $data['is_love'] = $favorite_model->check_loved($current_user_id, $scene_subject['_id'], 13) ? 1 : 0;       
        }

        $data['content_view_url'] = sprintf('%s/view/scene_subject_show?id=%d', Doggy_Config::$vars['app.url.api'], $scene_subject['_id']);

        // 分享内容
        $data['share_view_url'] = sprintf("%s/scene_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $data['_id']);
        $data['share_desc'] = Doggy_Dt_Filters_String::truncate(strip_tags($data['summary']), 80);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     * 增加分享数
     */
    public function record_share_count(){
        // 增加浏览量
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
			return $this->api_json('缺少请求参数!', 3001);
        }
        $model = new Sher_Core_Model_SceneSubject();
		$model->inc_counter('share_count', 1, $id);
    	return $this->api_json('操作成功!', 0, array('id'=>$id));
    }
	
}

