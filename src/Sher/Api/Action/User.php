<?php
/**
 * API 接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_User extends Sher_Api_Action_Base{

	protected $filter_user_method_list = array('execute', 'getlist', 'user_info');
	
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
		
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			$user_id = 10;
		}
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$has_scene = isset($this->stash['has_scene']) ? (int)$this->stash['has_scene'] : 1;
		
		$some_fields = array(
			'_id'=>1, 'account'=>1, 'nickname'=>1, 'stick'=>1, 'role_id' =>1, 'profile' => 1, 'fans_count' => 1, 'state'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();
		
		if($has_scene){
			// 测试
			//$query['scene_count'] = array('$gt'=>0);
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
				$options['sort_field'] = 'popular';
				break;
			case 2:
				$options['sort_field'] = 'topic_count';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_User::instance();
        $result = $service->get_user_list($query, $options);
		
		$follow_model = new Sher_Core_Model_Follow();
		$scene_service = Sher_Core_Service_SceneScene::instance();
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			
			// 判断是否被关注
			$result['rows'][$k]['is_love'] = 0;
			$query = array('user_id'=>$user_id,'follow_id'=>(int)$v['_id']);
			if($follow_model->first($query)){
				$result['rows'][$k]['is_love'] = 1;
			}
			
			// 情景信息
			$scene_size = 5;
			if($has_scene){
				// 测试
				//$scene = $scene_service->get_scene_scene_list(array('user_id'=>(int)$v['_id']),array('page'=>1,'size'=>5));
				$scene = $scene_service->get_scene_scene_list(array('user_id'=>20448),array('page'=>1,'size'=>$scene_size));
			}
			for($i=0;$i<$scene_size;$i++){
				$result['rows'][$k]['scene'][$i]['_id'] = $scene['rows'][$i]['_id'];
				$result['rows'][$k]['scene'][$i]['title'] = $scene['rows'][$i]['title'];
				$result['rows'][$k]['scene'][$i]['address'] = $scene['rows'][$i]['address'];
				$result['rows'][$k]['scene'][$i]['cover_url'] = $scene['rows'][$i]['cover']['thumbnails']['huge']['view_url'];
			}
			
			$result['rows'][$k]['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($v['created_on']);
			$result['rows'][$k]['address'] = $result['rows'][$k]['profile']['address'];
			
			// 屏蔽关键信息
			$filter_fields  = array('profile','ext_state','__extend__','birthday','last_char','mentor_info','is_ok','view_fans_url','view_follow_url','small_avatar_url','mini_avatar_url','medium_avatar_url','screen_name','id','role_id');
			for($i=0;$i<count($filter_fields);$i++){
				$key = $filter_fields[$i];
				unset($result['rows'][$k][$key]);
			}
		}
		
		// 过滤多余属性
        $filter_fields  = array();
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 获取用户信息
	 */
	public function user_info(){
		
		$id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		
		if(!$id){
			return $this->api_json('访问的用户不存在！', 3000);
		}
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->extend_load($id);
		
		if(empty($user)){
			return $this->api_json('用户未找到！', 3001);  
		}

		// 用户默认值
		$rank_id = 1;
		$rank_title = '鸟列兵';
		$bird_coin = 0;
	
		// 用户等级状态
		$user_ext_stat_model = new Sher_Core_Model_UserExtState();
		$user_ext = $user_ext_stat_model->extend_load($id);
		if($user_ext){
			$rank_id = $user_ext['rank_id'];
			$rank_title = $user_ext['user_rank']['title'];
		}
	
		// 用户实时积分
		$point_model = new Sher_Core_Model_UserPointBalance();
		$current_point = $point_model->load($id);
		// 鸟币
		$bird_coin = $current_point['balance']['money'];
	
		// 过滤用户字段
		$data = Sher_Core_Helper_FilterFields::wap_user($user);
	
		$data['rank_id'] = $rank_id;
		$data['rank_title'] = $rank_title;
		$data['bird_coin'] = $bird_coin;
		
		// 屏蔽关键信息
		$filter_fields  = array('account','email','phone','address','true_nickname','birthday','realname');
		for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($data[$key]);
        }
		
		//var_dump($data);die;
		return $this->api_json('请求成功', 0, $data);
	}

}
