<?php
/**
 * 场景管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneSight extends Sher_Api_Action_Base {
	
	public $stash = array(
		'id'   => '',
        'page' => 1,
        'size' => 10,
	);
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 场景列表
	 */
	public function getlist(){
		
		// http://www.taihuoniao.me/app/api/scene_sight/getlist?dis=1000&lat=39.9151190000&lng=116.4039630000
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:1000;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'title'=>1, 'des'=>1, 'scene_id'=>1, 'tags'=>1,
			'product' => 1, 'location'=>1, 'address'=>1, 'cover_id'=>1,
			'used_count'=>1, 'view_count'=>1, 'love_count'=>1, 'comment_count'=>1,
			'fine' => 1, 'is_check'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		// 基于地理位置的查询，从城市内查询
        $distance = isset($this->stash['dis']) ? (int)$this->stash['dis'] : 0; // 距离、半径
        $lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0; // 经度
        $lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0; // 纬度
		
		// 必须添加索引 db.scene_sight.ensureIndex({location: "2dsphere"})
		
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
		
		if($stick){
			if($stick == 1){
				$query['fine'] = 1;
			}
			if($stick == 2){
				$query['fine'] = 0;
			}
		}
		
		// 状态
		$query['status'] = 1;
		// 已审核
		$query['is_check']  = 1;
		
		if($scene_id){
			$query['scene_id']  = $scene_id;
		}
		
		if($user_id){
			$query['user_id']  = $scene_id;
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
        $service = Sher_Core_Service_SceneSight::instance();
        $result = $service->get_scene_sight_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			$result['rows'][$k]['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($v['created_on']);
			
			$result['rows'][$k]['product'] = array();
			if($v['product']){
				$result['rows'][$k]['product'] =$v['product'];
			}
			
			$user = array();
			
			if($v['user']){
				
				$user['user_id'] = $v['user']['_id'];
				$user['account'] = $v['user']['account'];
				$user['nickname'] = $v['user']['nickname'];
				$user['avatar_url'] = $v['user']['big_avatar_url'];
				$user['summary'] = $v['user']['summary'];
				$user['counter'] = $v['user']['counter'];
				$user['follow_count'] = $v['user']['follow_count'];
				$user['fans_count'] = $v['user']['fans_count'];
				$user['love_count'] = $v['user']['love_count'];
				$user['user_rank'] = $v['user_ext']['user_rank']['title'];
			}
			
			$result['rows'][$k]['scene_title'] = '';
			if($result['rows'][$k]['scene']){
				$result['rows'][$k]['scene_title'] = $v['scene']['title'];
			}
			
			$result['rows'][$k]['user_info'] = $user;
		}
		
		// 过滤多余属性
        $filter_fields  = array('scene','cover','user','user_ext','cover_id','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 场景情景
	 */
	public function save(){
		
		// http://www.taihuoniao.me/app/api/scene_sight/save?title=a&des=b&scene_id=31&tags=1,2,3&product_id=1,2,3&product_title=a,b,c&product_price=12,20,30&product_x=20,30,40&product_y=30,40,50&lat=39.9151190000&lng=116.4039630000&address=北京市
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$user_id = $this->current_user_id;
		//$user_id = 10;
		
		$data = array();
		$data['title'] = isset($this->stash['title']) ? $this->stash['title'] : '';
		$data['des'] = isset($this->stash['des']) ? $this->stash['des'] : '';
		$data['scene_id'] = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		$data['tags'] = isset($this->stash['tags']) ? $this->stash['tags'] : '';
		$data['product_id'] = isset($this->stash['product_id']) ? $this->stash['product_id'] : '';
		$data['product_title'] = isset($this->stash['product_title']) ? $this->stash['product_title'] : '';
		$data['product_price'] = isset($this->stash['product_price']) ? $this->stash['product_price'] : '';
		$data['product_x'] = isset($this->stash['product_x']) ? $this->stash['product_x'] : '';
		$data['product_y'] = isset($this->stash['product_y']) ? $this->stash['product_y'] : '';
		$data['address'] = isset($this->stash['address']) ? $this->stash['address'] : '';
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		
		if(!$data['title']){
			return $this->api_json('请求标题不能为空', 3000);
		}
		
		if(!$data['des']){
			return $this->api_json('请求描述不能为空', 3000);
		}
		
		if(!$data['scene_id']){
			return $this->api_json('请求情景id不能为空', 3000);
		}
		
		if(!$data['address']){
			return $this->api_json('请求地址不能为空', 3000);
		}
		
		if(!$data['tags']){
			return $this->api_json('请求标签不能为空', 3000);
		}
		
		$data['tags'] = explode(',',$data['tags']);
		foreach($data['tags'] as $k => $v){
			$data['tags'][$k] = (int)$v;
		}
		
		$data['product_id'] = explode(',',$data['product_id']);
		$data['product_title'] = explode(',',$data['product_title']);
		$data['product_price'] = explode(',',$data['product_price']);
		$data['product_x'] = explode(',',$data['product_x']);
		$data['product_y'] = explode(',',$data['product_y']);
		for($i = 0;$i < count($data['product_id']); $i++){
			$data['product'][$i]['id'] = (int)$data['product_id'][$i];
			$data['product'][$i]['title'] = $data['product_title'][$i];
			$data['product'][$i]['price'] = (float)$data['product_price'][$i];
			$data['product'][$i]['x'] = (float)$data['product_x'][$i];
			$data['product'][$i]['y'] = (float)$data['product_y'][$i];
		}
		unset($data['product_id']);
		unset($data['product_title']);
		unset($data['product_price']);
		unset($data['product_x']);
		unset($data['product_y']);
		
		// 上传图片
		//$this->stash['tmp'] = Doggy_Config::$vars['app.imges'];
		
		if(!isset($this->stash['tmp']) && empty($this->stash['tmp'])){
			return $this->api_json('请选择图片！', 3001);  
		}
		$file = base64_decode(str_replace(' ', '+', $this->stash['tmp']));
		$image_info = Sher_Core_Util_Image::image_info_binary($file);
		if($image_info['stat']==0){
			return $this->api_json($image_info['msg'], 3002);
		}
		if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
			return $this->api_json('图片格式不正确！', 3003);
		}
		$params = array();
		$new_file_id = new MongoId();
		$params['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SIGHT;
		$params['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SIGHT;
		$params['filename'] = $new_file_id.'.jpg';
		$params['parent_id'] = $id;
		$params['image_info'] = $image_info;
		$result = Sher_Core_Util_Image::api_image($file, $params);
		
		if($result['stat']){
			$data['cover_id'] = $result['asset']['id'];
		}else{
			return $this->api_json('上传失败!', 3005); 
		}
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_SceneSight();
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
			//var_dump($id);die;
			if(isset($data['cover_id']) && !empty($data['cover_id'])){
				$model->update_batch_assets($data['cover_id'], $id);
			}
			
			// 将场景保存到所属情景里面
			$model = new Sher_Core_Model_SceneScene();
			$result = $model->first($data['scene_id']);
			if($result){
				$option = array();
				$option['_id'] = $data['scene_id'];
				$option['sight'] = $result['sight'];
				array_push($option['sight'],$id);
				$result = $model->apply_and_update($option);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, null);
	}
	
	/**
     * 获取场景详情
     */
    public function view() {
        
        $id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', 3001);
        }
        
		$model = new Sher_Core_Model_SceneSight();
        $result  = $model->extend_load((int)$id);
		$result['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($result['created_on']);
		
		if (!$result) {
            return $this->api_json('请求内容为空!', true);
        }
		
		// 增加浏览量
		$model->inc((int)$id, 'view_count', 1);
        
        // 过滤多余属性
        $filter_fields  = array('type', 'cover_id', 'user', 'user_ext', 'cover', 'scene', '__extend__');
		
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
		$user['user_rank'] = $result['user_ext']['user_rank']['title'];
		
		$result['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
		$result['scene_title'] = $result['scene']['title'];
		$result['user_info'] = $user;
        
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
		//$user_id = 10;
		$model = new Sher_Core_Model_Favorite();
		$query = array(
			'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
			'event' => Sher_Core_Model_Favorite::EVENT_LOVE,
			'user_id' => $user_id
		);
		$res = $model->find($query);
		if($res){
			$result['is_love'] = 1;
		}else{
			$result['is_love'] = 0;
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
			$this->api_json('内容不存在', 3000);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$scene_sight_model = new Sher_Core_Model_SceneSight();
			
			foreach($ids as $id){
				$scene_sight = $scene_sight_model->load((int)$id);
				
				if (!empty($scene_sight)){
					$scene_sight_model->remove((int)$id);
          $scene_sight_model->mock_after_remove((int)$id, $scene_sight);
				}
			}
			
		}catch(Sher_Core_Model_Exception $e){
			$this->api_json('操作失败,请重新再试', 3001);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
	}
}

