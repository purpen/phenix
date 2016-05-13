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
		
		// http://www.taihuoniao.me/app/api/scene_scene/getlist?dis=1000&lat=39.9151190000&lng=116.4039630000
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:1000;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'user_id'=>1, 'des'=>1, 'sight'=>1, 'tags'=>1,
			'location'=>1, 'address'=>1, 'cover_id'=>1,'used_count'=>1,
			'view_count'=>1, 'subscription_count'=>1, 'love_count'=>1,
			'comment_count'=>1, 'is_check'=>1, 'stick'=>1, 'fine'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['fine']) ? (int)$this->stash['fine'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		
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
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1: // 最新推荐
				$options['sort_field'] = 'update:stick';
				break;
			case 2: // 最新精选
				$options['sort_field'] = 'update:fine';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SceneScene::instance();
        $result = $service->get_scene_scene_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
		}
		
		// 过滤多余属性
        $filter_fields  = array('cover','cover_id','view_url', 'user', 'summary', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		// title=test&des=test&tags=1&address=1&&lat=39.9151190000&lng=116.4039630000
		$user_id = $this->current_user_id;
		//$user_id = 10;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		$data = array();
		$data['title'] = isset($this->stash['title']) ? $this->stash['title'] : '';
		$data['des'] = isset($this->stash['des']) ? $this->stash['des'] : '';
		$data['tags'] = isset($this->stash['tags']) ? $this->stash['tags'] : '';
		$data['address'] = isset($this->stash['address']) ? $this->stash['address'] : '';
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
		
		$data['tags'] = explode(',',$data['tags']);
		foreach($data['tags'] as $k => $v){
			$data['tags'][$k] = (int)$v;
		}
		
		// 上传图片
		//$this->stash['tmp'] = Doggy_Config::$vars['app.imges'];
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
		
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_SceneScene();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
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
		
		return $this->api_json('提交成功', 0, array('current_user_id'=>$user_id));
	}
	
	/**
     * 获取情景详情
     */
    public function view() {
        
        $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', 3001);
        }
        
		$model = new Sher_Core_Model_SceneScene();
        $result  = $model->extend_load((int)$id);
		
		if (!$result) {
            return $this->api_json('请求内容为空!', true);
        }
		
		// 增加浏览量
    $rand = rand(1, 5);
		$model->inc_counter('view_count', $rand, (int)$id);
		$model->inc_counter('true_view_count', 1, (int)$id);
		$model->inc_counter('app_view_count', 1, (int)$id);
		
		$result['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
		$result['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['created_on']);
		
		$user = array();
		$user['user_id'] = $result['user']['_id'];
		$user['account'] = $result['user']['account'];
		$user['nickname'] = $result['user']['nickname'];
		$user['avatar_url'] = $result['user']['big_avatar_url'];
		$user['summary'] = $result['user']['summary'];
		$user['counter'] = $result['user']['counter'];
		$user['follow_count'] = $result['user']['follow_count'];
		$user['fans_count'] = $result['user']['fans_count'];
		$user['love_count'] = $result['user']['love_count'];
		$user['is_expert'] = isset($result['user']['identify']['is_expert']) ? (int)$result['user']['identify']['is_expert'] : 0;
		
		$result['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
		$result['user_info'] = $user;
        
		// 过滤多余属性
        $filter_fields  = array('type', 'cover_id', 'user', 'cover', 'sight', '__extend__');
        
        for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($result[$key]);
        }
		
		$tags_model = new Sher_Core_Model_SceneTags();
		//$result['tags'] = array(164,165,166);
		foreach($result['tags'] as $k => $v){
			$res = $tags_model->find_by_id((int)$v);
			$result['tag_titles'][$k] = '';
			if(isset($res['title_cn'])){
				$result['tag_titles'][$k] = $res['title_cn'];
			}
		}
		
		// 用户是否订阅该情景
		$user_id = $this->current_user_id;
		$model = new Sher_Core_Model_Favorite();
		$query = array(
			'target_id' => (int)$id,
			'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SCENE,
			'event' => Sher_Core_Model_Favorite::EVENT_SUBSCRIPTION,
			'user_id' => $user_id
		);
		$res = $model->find($query);
		if($res){
			$result['is_subscript'] = 1;
		}else{
			$result['is_subscript'] = 0;
		}
        
        //print_r($result);exit;
        return $this->api_json('请求成功', false, $result);
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
			  return $this->api_json('请先登录', 3000);   
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
				
				$has_sight = $sight_model->first(array('scene_id'=>(int)$id));
				
				if($has_sight){
					return $this->api_json('不允许操作！', 3003);
				}
				
        $scene_model->remove((int)$id);
        $scene_model->mock_after_remove((int)$id, $scene);

			} // endfor
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3001);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
	}
}

