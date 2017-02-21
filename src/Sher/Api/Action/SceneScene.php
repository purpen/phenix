<?php
/**
 * 情景管理
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
	 * 情景列表
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
	 * 提交情景
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
		
		$data = array();
		$data['title'] = isset($this->stash['title']) ? $this->stash['title'] : '';
		$data['des'] = isset($this->stash['des']) ? $this->stash['des'] : '';
		$data['tags'] = isset($this->stash['tags']) ? $this->stash['tags'] : '';
		$data['city'] = isset($this->stash['city']) ? $this->stash['city'] : '';
		$data['address'] = isset($this->stash['address']) ? $this->stash['address'] : '';
		$data['category_id'] = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0;
		$lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0;
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($lng), doubleval($lat)),
        );
		
		if(!$data['title']){
			return $this->api_json('请求标题不能为空', 3001);
		}
		
		if(!$data['des']){
			return $this->api_json('请求描述不能为空', 3002);
		}
		
		if(!$data['address']){
			return $this->api_json('请求地址不能为空', 3003);
		}
		
		if(!$data['tags']){
			return $this->api_json('请求标签不能为空', 3004);
		}
		
        if($mode=='create'){
            // 上传图片
            if(empty($this->stash['tmp'])){
                return $this->api_json('请选择图片！', 3005);  
            }
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
            }else{
                return $this->api_json('上传失败!', 3008); 
            }     
        }
		
		try{
			$model = new Sher_Core_Model_SceneScene();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
                $scene = $model->load($id);
                if(empty($scene) || $scene['user_id']!=$user_id){
 				    return $this->api_json('没有权限', 4001);                   
                }
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			// 上传成功后，更新所属的附件
			
			if(isset($data['cover_id']) && !empty($data['cover_id'])){
				$model->update_batch_assets(array($data['cover_id']), array($id));
      }
      // 更新全文索引
      Sher_Core_Helper_Search::record_update_to_dig((int)$id, 4);

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}
	
	/**
     * 详情
     */
    public function view() {
        
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
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
		$data['avatar_url'] = $scene['avatar']['thumbnails']['apc']['view_url'];
		//$data['banner_url'] = $scene['banner']['thumbnails']['aub']['view_url'];
		$data['created_at'] = Sher_Core_Helper_Util::relative_datetime($scene['created_on']);
		
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
        $assets = array();
        $asset_query = array('parent_id'=>$data['_id'], 'asset_type'=>Sher_Core_Model_Asset::TYPE_SCENE_SCENE);
        $asset_options['page'] = 1;
        $asset_options['size'] = 20;
        $asset_options['sort_field'] = 'latest';

        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
          foreach($asset_result['rows'] as $key=>$value){
            array_push($assets, $value['thumbnails']['apc']['view_url']);
          }
        }
        $data['covers'] = $assets;

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
}

