<?php
/**
 * API 接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_User extends Sher_Api_Action_Base{

	protected $filter_user_method_list = array('execute', 'getlist', 'user_info', 'find_user', 'activity_user');
	
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
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$has_scene = isset($this->stash['has_scene']) ? (int)$this->stash['has_scene'] : 1;
		
		$some_fields = array(
			'_id'=>1, 'account'=>1, 'nickname'=>1, 'stick'=>1, 'profile' => 1, 'fans_count' => 1, 'state'=>1, 'created_on'=>1, 'updated_on'=>1,
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
   * 发现好友
   */
  public function find_user(){
    $user_id = $this->current_user_id;
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
    // 0.最新；1.随机
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    // 1.过滤关注的用户和自己
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    // 要显示场景数量。0为不加载场景
    $sight_count = isset($this->stash['sight_count']) ? (int)$this->stash['sight_count'] : 0;

    $result = array();
    $user_arr = array();
    $follow_arr = array();

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key_id = Sher_Core_Util_Constant::DIG_FIU_USER_IDS;
    $dig = $dig_model->load($dig_key_id);
    if(empty($dig) || empty($dig['items'])){
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
          $follows = $follow_model->find($follow_query, $follow_options);
          $follow_max = count($follows);
          for($i=0; $i<$follow_max; $i++){
            array_push($follow_arr, $follows[$i]['follow_id']);
          }
          
          if($follow_max < $follow_size){
            $is_end = true;
            break;
          }
          $follow_page++;
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
        $scene_sight_list = $scene_sight_model->find(array('user_id'=>$user['_id'], 'deleted'=>0, 'is_check'=>1), array('page'=>1, 'size'=>$sight_count));
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

		$bird_coin = 0;
	
		// 用户实时积分
		$point_model = new Sher_Core_Model_UserPointBalance();
		$current_point = $point_model->load($id);
		// 鸟币
		$bird_coin = $current_point['balance']['money'];
	
		// 过滤用户字段
		$data = Sher_Core_Helper_FilterFields::wap_user($user);

		$data['bird_coin'] = $bird_coin;
		
		// 屏蔽关键信息
		if($this->current_user_id != $id){
			$filter_fields  = array('email','phone','address','true_nickname','birthday','realname','counter');
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

        if($this->current_user_id != $id){
            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_home_visited', $id);
        }

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     *最Fiu伙伴
     */
    public function activity_user(){

		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$sort = isset($this->stash['sort'])?(int)$this->stash['sort']:1;
		$kind = isset($this->stash['kind'])?(int)$this->stash['kind']:1;
		$day = isset($this->stash['day'])?(int)$this->stash['day']:0;
		$week = isset($this->stash['week'])?(int)$this->stash['week']:0;
		$month = isset($this->stash['month'])?(int)$this->stash['month']:0;

        $query = array();

        // 昨天的日期
        $yesterday = (int)date('Ymd' , strtotime('-1 day'));

      if($day){
        $query['day'] = (int)$day;
      }
      if($week){
        $query['week'] = (int)$week;
        $query['week_latest'] = 1;
      }
      if($month){
        $query['month'] = (int)$month;
        $query['month_latest'] = 1;
      }

      if($kind){
        $query['kind'] = (int)$kind;
      }

      if(empty($day)){
        $query['day'] = $yesterday;
      }

	  $sort_field = 'latest';
      if($sort){
        switch((int)$sort){
          case 1:
            if($day){
              $sort_field = 'day_point';
            }elseif($week){
              $sort_field = 'week_point';           
            }elseif($month){
              $sort_field = 'month_point';           
            }else{
              $sort_field = 'sort_point';           
            }
            break;
          case 2:
            if($day){
              $sort_field = 'day_money';
            }elseif($week){
              $sort_field = 'week_money';           
            }elseif($month){
              $sort_field = 'month_money';           
            }else{
              $sort_field = 'sort_money';
            }
            break;
        }
      }

        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;

        $service = Sher_Core_Service_UserPointStat::instance();
        $result = $service->get_all_list($query,$options);

 		// 重建数据结果
		$data = array();
        for($i=0;$i<count($result['rows']);$i++){
            $user = $result['rows'][$i]['user'];
            $data[$i]['_id'] = $user['_id'];
            $data[$i]['nickname'] = $user['nickname'];
            $data[$i]['avatar_url'] = $user['medium_avatar_url'];
            $data[$i]['summary'] = !empty($user['summary']) ? $user['summary'] : '';
            $data[$i]['is_expert'] = isset($user['identify']['is_expert']) ? (int)$user['identify']['is_expert'] : 0;
            $data[$i]['label'] = isset($user['profile']['label']) ? $user['profile']['label'] : '';
            $data[$i]['expert_label'] = isset($user['profile']['expert_label']) ? $user['profile']['expert_label'] : '';
            $data[$i]['expert_info'] = isset($user['profile']['expert_info']) ? $user['profile']['expert_info'] : '';
            $data[$i]['rank_id'] = isset($user['ext_state']) ? $user['ext_state']['rank_id'] : 1;
        }

        $result['rows'] = $data;
        return $this->api_json('请求成功', 0, $result);
    }

    /*
     * 送积分/
     *
     */
    public function send_exp(){
        $user_id = $this->current_user_id;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
        $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : null;

        $target_user_id = 0;
        $model = null;

        $exp_count = 0;

        // 增长积分
        $service = Sher_Core_Service_Point::instance();

        switch($type){
            case 1: // 情境
                $model = new Sher_Core_Model_SceneScene();
                break;
            case 2: // 场景
                $model = new Sher_Core_Model_SceneSight();
                $scene_sight = $model->load((int)$target_id);
                if($scene_sight){
                    $target_user_id = $scene_sight['user_id'];
                    if($evt==1){    // 分享
                        $exp_count = 5;
                        $service->send_event('evt_sight_by_share', $target_user_id);                  
                        $service->send_event('evt_sight_share', $user_id);                   
                    }
                }
                break;
            case 3: // 产品
                $model = new Sher_Core_Model_SceneProduct();
                break;
            case 4: // 专题
                $model = new Sher_Core_Model_SceneSubject();
                break;
            default:
                
        }   // end switch

        return $this->api_json('请求成功', 0, array('exp'=>$exp_count));
    
    }

}
