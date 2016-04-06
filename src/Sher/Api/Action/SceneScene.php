<?php
/**
 * 情景管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneScene extends Sher_Api_Action_Base {
	
	public $stash = array(
		'id'   => '',
        'page' => 1,
        'size' => 10,
	);
	
	protected $filter_user_method_list = array('execute', 'getlist','save','delete');

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
			'comment_count'=>1, 'is_check'=>1, 'status'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
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
		
		if($stick){
			if($stick == 1){
				$query['stick'] = 1;
			}
			if($stick == 2){
				$query['stick'] = 0;
			}
		}
		
		// 状态
		$query['status'] = 1;
		// 已审核
		$query['is_check']  = 1;
		
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
        $service = Sher_Core_Service_SceneScene::instance();
        $result = $service->get_scene_scene_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			// 备用
		}
		
		// 过滤多余属性
        $filter_fields  = array('view_url', 'sight', 'user', 'summary', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		$user_id = $this->current_user_id;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
		$data['address'] = $this->stash['address'];
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		//$data['asset'] = isset($this->stash['asset'])?$this->stash['asset']:array();
		
		if(empty($data['title']) || empty($data['des'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['address']) || empty($data['address'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['location']['coordinates']['lat']) || empty($data['location']['coordinates']['lat'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		$data['tags'] = explode(',',$data['tags']);
		foreach($data['tags'] as $k => $v){
			$data['tags'][$k] = (int)$v;
		}
		
		// 上传图片
		
		if(empty($this->stash['tmp'])){
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
		$params['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$params['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
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
				$this->update_batch_assets($data['cover_id'], $id);
			}		
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('current_user_id'=>$user_id));
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
			$model = new Sher_Core_Model_SceneScene();
			
			foreach($ids as $id){
				$result = $model->load((int)$id);
				
				if (!empty($result)){
					$model->remove((int)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			$this->api_json('操作失败,请重新再试', 3001);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
	}
}

