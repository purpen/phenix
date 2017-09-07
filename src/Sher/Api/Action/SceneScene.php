<?php
/**
 * 地盘管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneScene extends Sher_Api_Action_Base {
	
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
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'sub_title'=>1, 'user_id'=>1, 'des'=>1, 'sight'=>1, 'tags'=>1,
			'location'=>1, 'address'=>1, 'cover_id'=>1,'used_count'=>1, 'category_id'=>1,
			'view_count'=>1, 'subscription_count'=>1, 'love_count'=>1, 'deleted'=>1, 'city'=>1,
			'comment_count'=>1, 'is_check'=>1, 'stick'=>1, 'stick_on'=>1, 'fine'=>1, 'fine_on'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
            'avatar'=>1, 'cover'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['fine']) ? (int)$this->stash['fine'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		
		// 基于地理位置的查询，从城市内查询
        $distance = isset($this->stash['dis']) ? (int)$this->stash['dis'] : 0; // 距离、半径
        $lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0; // 经度
        $lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0; // 纬度
		
		// 必须添加索引 db.scene_scene.ensureIndex({location: "2dsphere"})
		
		# 按照半径搜索: 搜索半径内的所有的点,按照由近到远排序
        if (!empty($lat) && !empty($lng)) {
            $point = array(doubleval($lng), doubleval($lat));
            $distance = $distance/1000;
            
            if ($distance) {
                $query['location'] = array(
                  '$geoWithin' => array(
                      '$centerSphere' => array($point, $distance/6371)
                  )  
                );
            } else {
                $query['location'] = array(
                  '$nearSphere' => $point
                );
            }
        }

        if($category_id){
            $query['category_id'] = $category_id;
        }

        $query['is_check'] = 1;
		
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
		
		// 已审核
		$query['is_check']  = 1;
		
		if($user_id){
			$query['user_id']  = $user_id;
		}

    $query['deleted'] = 0;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1: // 最新推荐
				$options['sort_field'] = 'stick';
				break;
			case 2: // 最新精选
				$options['sort_field'] = 'fine';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SceneScene::instance();
        $result = $service->get_scene_scene_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['avatar_url'] = $result['rows'][$k]['avatar']['thumbnails']['apc']['view_url'];
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['apc']['view_url'];
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);

            $category = array();
            if(isset($result['rows'][$k]['category'])){
                $category['_id'] = $result['rows'][$k]['category']['_id'];
                $category['title'] = $result['rows'][$k]['category']['title'];
            }
            $result['rows'][$k]['category'] = $category;
		}
		
		// 过滤多余属性
        $filter_fields  = array('_id', 'title', 'sub_title', 'avatar_url', 'cover_url', 'category', 'user_id', 'location', 'city', 'address');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 1);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 提交地盘
	 */
	public function save(){
		
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录', 3000);   
		}

        if(empty($id)){
            $mode = 'create';
        }else{
            $mode = 'edit';
        }
		

		$title = isset($this->stash['title']) ? $this->stash['title'] : '';
		$sub_title = isset($this->stash['sub_title']) ? $this->stash['sub_title'] : '';
		$des = isset($this->stash['des']) ? $this->stash['des'] : '';
		$tags = isset($this->stash['tags']) ? $this->stash['tags'] : '';
		$city = isset($this->stash['city']) ? $this->stash['city'] : '';
        $bright_spot = isset($this->stash['bright_spot']) ? $this->stash['bright_spot'] : '';
		$address = isset($this->stash['address']) ? $this->stash['address'] : '';
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$extra_shop_hours = isset($this->stash['extra_shop_hours']) ? $this->stash['extra_shop_hours'] : '';
		$extra_tel = isset($this->stash['extra_tel']) ? $this->stash['extra_tel'] : '';
		$lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0;
		$lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0;

		$data = array();
		
		if($title){
            $data['title'] = $title;
		}
		
		if($des){
		    $data['des'] = $des;
		}

		if($tags){
		    $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$tags)));
		}

		if($city){
		    $data['city'] = $city;
		}
		
		if($address){
		    $data['address'] = $address;
		}

		if($category_id){
		    $data['category_id'] = $category_id;
		}
		
		if($sub_title){
		    $data['sub_title'] = $sub_title;
		}

        if($extra_shop_hours){
            $data['extra.shop_hours'] = $extra_shop_hours;
        }
        if($extra_tel){
            $data['extra.tel'] = $extra_tel;
        }

        // 亮点
        if($bright_spot){
            $bright_spot_arr = json_decode($bright_spot);
            if(is_array($bright_spot_arr) && !empty($bright_spot_arr)){
                $new_bright_spot = array();
                for($i=0;$i<count($bright_spot_arr);$i++){
                    if(empty($bright_spot_arr[$i])) continue;
                    array_push($new_bright_spot, $bright_spot_arr[$i]);
                }
                if(!empty($new_bright_spot)){
                    $data['bright_spot'] = $new_bright_spot;
                }
            }
        }

        if($lng || $lat){
            $data['location'] = array(
                'type' => 'Point',
                'coordinates' => array(doubleval($lng), doubleval($lat)),
            );       
        }
		

        // 上传封面
        if(!empty($this->stash['tmp'])){
            $file = base64_decode(str_replace(' ', '+', $this->stash['tmp']));
            $image_info = Sher_Core_Util_Image::image_info_binary($file);
            if($image_info['stat']==0){
                return $this->api_json($image_info['msg'], 3006);
            }
            if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
                return $this->api_json('图片格式不正确！', 3007);
            }
            $params = array();
            $new_file_id = new MongoId();
            $params['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
            $params['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
            $params['filename'] = $new_file_id.'.jpg';
            $params['parent_id'] = $id;
            $params['user_id'] = $user_id;
            $params['image_info'] = $image_info;
            $result = Sher_Core_Util_Image::api_image($file, $params);
            
            if($result['stat']){
                $data['cover_id'] = $result['asset']['id'];
                $cover_asset_id = $result['asset']['id'];
            }else{

            } 
        }

        // 上传头像
        $avatar_asset_id = null;
        if(!empty($this->stash['avatar_tmp'])){
            $file = base64_decode(str_replace(' ', '+', $this->stash['avatar_tmp']));
            $image_info = Sher_Core_Util_Image::image_info_binary($file);
            if($image_info['stat']==0){
                return $this->api_json($image_info['msg'], 3006);
            }
            if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
                return $this->api_json('图片格式不正确！', 3007);
            }
            $params = array();
            $new_file_id = new MongoId();
            $params['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
            $params['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_AVATAR;
            $params['filename'] = $new_file_id.'.jpg';
            $params['parent_id'] = $id;
            $params['user_id'] = $user_id;
            $params['image_info'] = $image_info;
            $result = Sher_Core_Util_Image::api_image($file, $params);
            
            if($result['stat']){
                $avatar_asset_id = $result['asset']['id'];
                $data['avatar_id'] = $result['asset']['id'];
            }else{

            } 
        }

        if(empty($data)){
            return $this->api_json('缺少请求参数！', 3001);
        }
    
		try{
			$model = new Sher_Core_Model_SceneScene();

			// 新建记录
			if(empty($id)){
                $exist = $model->first(array('user_id'=>$user_id));
                if(empty($exist)){
				    $data['user_id'] = $user_id;
                    $ok = $model->apply_and_save($data);
                    if(!$ok){
                        return $this->api_json('创建失败!', 3002);
                    }
                    $scene = $model->get_data();
                    $id = $scene['_id'];
                }else{  // 此用户含有店铺，直接更新
                    $ok = $model->update_set($exist['_id'], $data);
                    if(!$ok){
                        return $this->api_json('更新失败!', 3002);   
                    }
                    $id = $exist['_id'];
                }
				
			}else{
                $scene = $model->load($id);
                if(empty($scene) || $scene['user_id']!=$user_id){
 				    return $this->api_json('没有权限', 3003);
                }
				$ok = $model->update_set($id, $data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 3004);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($cover_asset_id) && !empty($cover_asset_id)){
				$model->update_batch_assets(array($cover_asset_id), $id);
            }
			if(isset($avatar_asset_id) && !empty($avatar_asset_id)){
				$model->update_batch_assets(array($avatar_asset_id), $id);
            }
            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig((int)$id, 4);

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api地盘保存失败：".$e->getMessage());
			return $this->api_json('地盘保存失败:'.$e->getMessage(), 3005);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}
	
	/**
     * 详情
     */
    public function view() {
        
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $is_edit = isset($this->stash['is_edit']) ? (int)$this->stash['is_edit'] : 0;
		
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', 3001);
        }
        
		$model = new Sher_Core_Model_SceneScene();
        $scene = $model->extend_load((int)$id);
		
		if (empty($scene) || $scene['deleted']==1) {
            return $this->api_json('地盘不存在或已删除!', 3002);
        }

		if ($scene['is_check']==0) {
            return $this->api_json('地盘未通过审核!', 3003);
        }
		
		// 增加浏览量
        $rand = rand(1, 5);
		$model->inc_counter('view_count', $rand, $id);
		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('app_view_count', 1, $id);

        $data = array();

		$data['_id'] = $scene['_id'];
		$data['title'] = $scene['title'];
		$data['sub_title'] = $scene['sub_title'];
		$data['category_id'] = $scene['category_id'];
		$data['product_tags'] = isset($scene['product_tags']) ? $scene['product_tags'] : array();
		$data['avatar_url'] = $scene['avatar']['thumbnails']['apc']['view_url'];
		//$data['banner_url'] = $scene['banner']['thumbnails']['aub']['view_url'];
		$data['created_at'] = Sher_Core_Helper_Util::relative_datetime($scene['created_on']);

        $category = array();
        if(isset($scene['category'])){
            $category['_id'] = $scene['category']['_id'];
            $category['title'] = $scene['category']['title'];
        }
        $data['category'] = $category;
		
		$user = array();
        if($scene['user']){
            $user['_id'] = $scene['user']['_id'];
            $user['nickname'] = $scene['user']['nickname'];
            $user['avatar_url'] = $scene['user']['big_avatar_url'];
            $user['is_expert'] = isset($scene['user']['identify']['is_expert']) ? (int)$scene['user']['identify']['is_expert'] : 0;
            $user['label'] = isset($scene['user']['profile']['label']) ? $scene['user']['profile']['label'] : '';
            $user['expert_label'] = isset($scene['user']['profile']['expert_label']) ? $scene['user']['profile']['expert_label'] : '';
            $user['expert_info'] = isset($scene['user']['profile']['expert_info']) ? $scene['user']['profile']['expert_info'] : '';
        }else{
            return $this->api_json('该用户不存在!', 3004);
        }
		$data['user'] = $user;
        $data['love_count'] = $scene['love_count'];
        $data['view_count'] = $scene['app_view_count'];

        $data['city'] = $scene['city'];
        $data['address'] = $scene['address'];
        $data['location'] = $scene['location'];

        //验证是否收藏或喜欢
        $favorite_model = new Sher_Core_Model_Favorite();
        $data['is_love'] = $favorite_model->check_loved($this->current_user_id, $data['_id'], 11) ? 1 : 0;
        $data['is_favorite'] = $favorite_model->check_favorite($this->current_user_id, $data['_id'], 11) ? 1 : 0;

        $asset_service = Sher_Core_Service_Asset::instance();

        //返回图片数据--banner
        $assets = array();
        $asset_query = array('parent_id'=>$data['_id'], 'asset_type'=>Sher_Core_Model_Asset::TYPE_SCENE_BANNER);
        $asset_options['page'] = 1;
        $asset_options['size'] = 5;
        $asset_options['sort_field'] = 'latest';

        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
          foreach($asset_result['rows'] as $key=>$value){
            array_push($assets, $value['thumbnails']['aub']['view_url']);
          }
        }
        $data['banners'] = $assets;

        //返回图片数据--cover
        $assets = $n_assets = array();
        $asset_query = array('parent_id'=>$data['_id'], 'asset_type'=>Sher_Core_Model_Asset::TYPE_SCENE_SCENE);
        $asset_options['page'] = 1;
        $asset_options['size'] = 20;
        $asset_options['sort_field'] = 'latest';

        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
            foreach($asset_result['rows'] as $key=>$value){
                array_push($n_assets, array('id'=>(string)$value['_id'], 'url'=>$value['thumbnails']['apc']['view_url']));
                if((string)$value['_id'] != $scene['cover_id']){
                    array_push($assets, $value['thumbnails']['apc']['view_url']);
                }
            }
        }
        if(isset($scene['cover']) && !empty($scene['cover'])){
            array_unshift($assets, $scene['cover']['thumbnails']['apc']['view_url']);
        }
        $data['covers'] = $assets;
        if($is_edit){
            $data['n_covers'] = $n_assets;
        }

        $data['des'] = $scene['des'];
        $data['tags'] = $scene['tags'];
        $data['extra'] = $scene['extra'];
        $data['score_average'] = $scene['score_average'];
        $data['bright_spot'] = $scene['bright_spot'];
        $data['view_url'] = sprintf("%s/storage/view?id=%d", Doggy_Config::$vars['app.domain.mobile'], $data['_id']);
        
		// 过滤多余属性
        //$filter_fields = array('cover_id', 'cover', 'avatar', 'banner', '__extend__');
        //$scene = Sher_Core_Helper_FilterFields::filter_field($scene, $filter_fields, 1);
        
        return $this->api_json('请求成功', false, $data);
    }
	
	/**
	 * 删除
	 */
	public function delete(){
		
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->api_json('内容不存在', 3000);
		}
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3001);   
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$scene_model = new Sher_Core_Model_SceneScene();
			$sight_model = new Sher_Core_Model_SceneSight();
			
			foreach($ids as $id){
				$scene = $scene_model->load((int)$id);
        if(empty($scene)){
 					return $this->api_json('删除的内容不存在！', 3001);       
        }
        if($scene['user_id'] != $user_id){
  				return $this->api_json('没有权限！', 3002);        
        }
				
				$has_sight = $sight_model->first(array('scene_id'=>(int)$id, 'deleted'=>0));
				
				if($has_sight){
					return $this->api_json('不允许操作！', 3003);
				}
				
                $ok = $scene_model->mark_remove((int)$id);
                if($ok){
                    $scene_model->mock_after_remove((int)$id, $scene);
                }

			} // endfor
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3001);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
	}

	/**
	 * 地盘产品列表
	 */
	public function product_list(){
		
		// 请求参数
    $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
    $scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
    $tag = isset($this->stash['tag']) ? $this->stash['tag'] : '';
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;

    if(empty($scene_id)) {
			return $this->api_json('地盘ID不能为空！', 3001); 
    }
		
		$some_fields = array(
			'_id'=>1, 'scene_id'=>1, 'product_id'=>1, 'tag'=>1, 'user_id'=>1, 'type'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);

		$product_some_fields = array(
            '_id', 'title', 'short_title', 'sale_price', 'market_price',
			'cover_id', 'commision_percent',
			'view_count', 'favorite_count', 'love_count', 'comment_count', 'deleted','created_on',
		);
		
		$query   = array();
		$options = array();

    if($scene_id) {
      $query['scene_id'] = $scene_id;
    }
    if($product_id) {
      $query['product_id'] = $product_id;
    }
    if($tag) {
      $query['tag'] = $tag;
    }
		

		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_ZoneProductLink::instance();
        $result = $service->get_zone_product_list($query, $options);

        $product_model = new Sher_Core_Model_Product();

        // 重新数据
        for($i=0;$i<count($result['rows']);$i++){
            $item = $result['rows'][$i];
            $result['rows'][$i]['_id'] = (string)$item['_id'];

            // 重建商品数据结果
            $product_item = array();
            $product = $product_model->extend_load($item['product_id']);
            if($product){
              for($j=0;$j<count($product_some_fields);$j++){
                $product_key = $product_some_fields[$j];
                $product_item[$product_key] = isset($product[$product_key]) ? $product[$product_key] : null;
              }
              // 封面图url
              $product_item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];           
            }
            $result['rows'][$i]['product'] = $product_item;

        }
		
		// 过滤多余属性
        $filter_fields  = array('_id', 'scene_id', 'tag', 'product_id', 'type', 'status', 'product', 'created_on');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 1);
		
		return $this->api_json('请求成功', 0, $result);
	}

  /**
   * 添加地盘产品
   */
  public function add_product() {
      $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
      $scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
      if(empty($product_id) || empty($scene_id)){
        return $this->api_json('缺少请求参数!', 3001);
      }
      $user_id = $this->current_user_id;
      $scene_model = new Sher_Core_Model_SceneScene();
      $scene = $scene_model->load($scene_id);
      if(empty($scene)){
        return $this->api_json('地盘不存在!', 3002);
      }
      if($scene['user_id'] != $user_id){
        return $this->api_json('没有权限!', 3003);
      }

      $product_model = new Sher_Core_Model_Product();
      $product = $product_model->load($product_id);
      if(empty($product)) {
        return $this->api_json('产品不存在或已删除!', 3004);
      }

      $row = array(
          'scene_id' => $scene_id,
          'product_id' => $product_id,
      );
		$model = new Sher_Core_Model_ZoneProductLink();
        $item = $model->first(array('scene_id'=>$scene_id, 'product_id'=>$product_id));
        if(!empty($item)){
 		    return $this->api_json('不能重复添加!', 3005);
        }

        $ok = $model->apply_and_save($row);
        if(!$ok){
  		    return $this->api_json('添加失败!', 3006);            
        }
        $item = $model->get_data();
        $id = (string)$item['_id'];
  		return $this->api_json('添加成功!', 0, array('id'=>$id)); 
  
  }

  /**
   * 删除产品
   */
  public function del_product() {
      $id = isset($this->stash['id']) ? $this->stash['id'] : null;
      if(empty($id)){
        return $this->api_json('缺少请求参数!', 3001);
      }
      $user_id = $this->current_user_id;

		  $model = new Sher_Core_Model_ZoneProductLink();
      $item = $model->load($id);
      if(empty($item)) {
         return $this->api_json('删除的内容不存在!', 3002);     
      }
      $scene_model = new Sher_Core_Model_SceneScene();
      $scene = $scene_model->load($item['scene_id']);
      if(empty($scene)){
        return $this->api_json('地盘不存在!', 3003);
      }
      if($scene['user_id'] != $user_id){
        return $this->api_json('没有权限!', 3004);
      }
      $ok = $model->remove($id);
      if(!$ok) {
         return $this->api_json('删除失败!', 3005);     
      }

  		return $this->api_json('删除成功!', 0, array('id'=>$id));  
  }

}

