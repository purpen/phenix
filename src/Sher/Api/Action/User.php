<?php
/**
 * API 接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_User extends Sher_Api_Action_Base{

	protected $filter_user_method_list = array('execute', 'getlist', 'user_info', 'find_user');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 场景列表--性能太差，以后去掉此接口
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
		
		if($sort == 2){
			$total_count = abs($result['rows'][0]['fans_count'] - $result['rows'][count($result['rows'])-1]['fans_count']);
			$every_count = round($total_count/3);
		}
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			
			// 返回头像大小类型
			$result['rows'][$k]['avatar_size_type'] = 1;
			if(isset($every_count) && $every_count){
				if($v['fans_count']>=0 && $v['fans_count'] < $every_count){
					$result['rows'][$k]['avatar_size_type'] = 1;
				}
				if($v['fans_count']>=$every_count && $v['fans_count'] < 2*$every_count){
					$result['rows'][$k]['avatar_size_type'] = 2;
				}
				if($v['fans_count']>=2*$every_count && $v['fans_count'] < 3*$every_count){
					$result['rows'][$k]['avatar_size_type'] = 3;
				}
			}
			
			// 判断是否被关注
			$result['rows'][$k]['is_love'] = 0;
			if($follow_model->has_exist_ship($user_id, $v['_id'])){
				$result['rows'][$k]['is_love'] = 1;
			}
			
			// 情景信息
			$scene_size = 5;
			if($has_scene){
				// 测试
				//$scene = $scene_service->get_scene_scene_list(array('user_id'=>(int)$v['_id']),array('page'=>1,'size'=>5));
				$scene = $scene_service->get_scene_scene_list(array('user_id'=>20448),array('page'=>1,'size'=>$scene_size));
				foreach($scene['rows'] as $key => $val){
					$result['rows'][$k]['scene'][$key]['_id'] = $val['_id'];
					$result['rows'][$k]['scene'][$key]['title'] = $val['title'];
					$result['rows'][$k]['scene'][$key]['address'] = $val['address'];
					$result['rows'][$k]['scene'][$key]['cover_url'] = $val['cover']['thumbnails']['huge']['view_url'];
				}
			}
			
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
			$result['rows'][$k]['address'] = isset($result['rows'][$k]['profile']['address']) ? $result['rows'][$k]['profile']['address'] : '';
			
			// 屏蔽关键信息
			$filter_fields  = array('profile','ext_state','__extend__','birthday','last_char','mentor_info','is_ok','view_fans_url','view_follow_url','small_avatar_url','mini_avatar_url','big_avatar_url','screen_name','id','role_id');
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
   * 发现好友，最Fiu伙伴
   */
  public function find_user(){
    $user_id = $this->current_user_id;
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
    // 0.最新；1.随机
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    // 要显示场景数量。0为不加载场景
    $sight_count = isset($this->stash['sight_count']) ? (int)$this->stash['sight_count'] : 0;

    $result = array();
    $user_arr = array();
    $follow_arr = array();

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key_id = Sher_Core_Util_Constant::DIG_FIU_USER_IDS;
    $dig = $dig_model->load($dig_key_id);
    if(empty($dig || empty($dig['items']))){
      return $this->api_json('empty', 0, array('users'=>$result));
    }

    $user_model = new Sher_Core_Model_User();
    $follow_model = new Sher_Core_Model_Follow();
    $scene_sight_model = new Sher_Core_Model_SceneSight();   

    if($type==1){ // 过滤已关注的用户和当前用户
      if($user_id){
        $follow_page = 1;
        $follow_size = 100;
        $is_end = false;
        $follow_query = array();
        $follow_options = array();
        $follow_query['user_id'] = $user_id;
        $follow_options['size'] = $follow_size;
        
        while(!$is_end){
          $options['page'] = $follow_page;
          $follows = $order_model->find($follow_query, $follow_options);
          $follow_max = count($follows);
          for($i=0; $i<$follow_max; $i++){
            array_push($follow_arr, $follows[$i]['follow_id']);
          }
          
          if($follow_max < $follow_size){
            $is_end = true;
            break;
          }
          $follow_size++;
        } // end while
        array_push($follow_arr, $user_id);
      } // endif user_id
    }elseif($type==2){
    
    }

    // 取前Ｎ个数量
    $dig['items'] = array_slice($dig['items'], 0, $size);

    // 整理数据
    for($i=0;$i<count($dig['items']);$i++){
      if(!empty($follow_arr)){
        for($j=0;$j<count($follow_arr);$j++){
          if(in_array($follow_arr[$j], $dig['items'])){
            continue;
          }
        }
      }
      array_push($user_arr, $dig['items'][$i]);
    }

    // 打乱数组
    if($sort==1){
      shuffle($user_arr);
    }

    // 加载数据
    for($i=0;$i<count($user_arr);$i++){
      $user = $user_model->extend_load((int)$user_arr[$i]);
      if(empty($user)){
        continue;
      }
      // 过滤用户字段
      $item = Sher_Core_Helper_FilterFields::wap_user($user);
			// 判断是否被关注
			$item['is_love'] = 0;
			if($follow_model->has_exist_ship($user_id, $user['_id'])){
				$item['is_love'] = 1;
			}
      $item['scene_sight'] = array();
      if($sight_count){
        $scene_sight_list = $scene_sight_model->find(array('user_id'=>$user['_id'], 'is_check'=>1), array('page'=>1, 'size'=>$sight_count));
        for($j=0;$j<count($scene_sight_list);$j++){
          $scene_sight = $scene_sight_model->extended_model_row($scene_sight_list[$j]);
          $item['scene_sight'][$j]['_id'] = $scene_sight['_id'];
          $item['scene_sight'][$j]['title'] = $scene_sight['title'];
          $item['scene_sight'][$j]['address'] = $scene_sight['address'];
          $item['scene_sight'][$j]['cover_url'] = $scene_sight['cover']['thumbnails']['huge']['view_url'];
        }
      } // endif sight_count
      array_push($result, $item);
    } // endfor
    
    return $this->api_json('success', 0, array('users'=>$result));
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
		//var_dump($user);die;
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

		// 是否有头图
		$data['head_pic_url'] = null;
		if(isset($user['head_pic']) && !empty($user['head_pic'])){
		  $asset_model = new Sher_Core_Model_Asset();
		  $asset = $asset_model->extend_load($user['head_pic']);
		  if($asset){
			$data['head_pic_url'] = $asset['thumbnails']['huge']['view_url'];
		  }
		}
		
		// 屏蔽关键信息
		if($this->current_user_id != $id){
			$filter_fields  = array('account','email','phone','address','true_nickname','birthday','realname','counter');
			for($i=0;$i<count($filter_fields);$i++){
				$key = $filter_fields[$i];
				unset($data[$key]);
			}   
		}
		
		$follow_model = new Sher_Core_Model_Follow();
		// 判断是否被关注
		$data['is_love'] = 0;
		if($follow_model->has_exist_ship($this->current_user_id, $id)){
			$data['is_love'] = 1;
		}
		//var_dump($data);die;
		return $this->api_json('请求成功', 0, $data);
	}

}
