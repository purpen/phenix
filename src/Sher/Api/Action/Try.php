<?php
/**
 * 试用API接口
 * @author tianshuai
 */
class Sher_Api_Action_Try extends Sher_Api_Action_Base {
	
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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:6;
    $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
    $stick = isset($this->stash['stick'])?(int)$this->stash['stick']:0;
    $user_id = isset($this->stash['user_id'])?(int)$this->stash['user_id']:0;
		
		$query   = array();
		$options = array();

    //显示的字段
    $options['some_fields'] = array(
      '_id'=> 1, 'title'=>1, 'short_title'=>1, 'description'=>1, 'cover_id'=>1, 'banner_id'=>1, 'step_stat'=>1, 'sticked'=>1,
      'tags'=>1, 'comment_count'=>1, 'created_on'=>1, 'kind'=>1,
      'try_count'=>1, 'apply_count'=>1, 'report_count'=>1, 'want_count'=>1, 'view_count'=>1,
      'buy_url'=>1, 'open_limit'=>1, 'open_limit'=>1, 'apply_term'=>1, 'term_count'=>1,
      'start_time'=>1, 'end_time'=>1, 'publish_time'=>1, 'state'=>1, 'price'=>1, 'pass_users'=>1,
    );
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($stick){
			$query['sticked'] = 1;
    }

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'sticked:latest';
				break;
		}
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;

		// 开启查询
    $service = Sher_Core_Service_Try::instance();
    $result = $service->get_try_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
        $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['aub']['view_url'];
			// banner图url
			$data[$i]['banner_url'] = $result['rows'][$i]['banner']['thumbnails']['aub']['view_url'];

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
    $user_id = $this->current_user_id;
		if(empty($id)){
			return $this->api_json('访问的主题不存在！', 3000);
		}
		
		// 是否允许编辑
		$editable = false;
		$result = array();
		
		$model = new Sher_Core_Model_Try();
		$try = $model->extend_load($id);
		
		if(empty($try)){
			return $this->api_json('试用不存在或已被删除！', 3001);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);

    //显示的字段
    $some_fields = array(
      '_id', 'title', 'short_title', 'description', 'cover_id', 'banner_id', 'step_stat', 'sticked',
      'tags', 'comment_count', 'created_on', 'kind', 'wap_view_url',
      'try_count', 'apply_count', 'report_count', 'want_count', 'view_count',
      'buy_url', 'open_limit', 'open_limit', 'apply_term', 'term_count',
      'start_time', 'end_time', 'publish_time', 'state', 'price', 'pass_users',
    );

    // 重建数据结果
    $data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($try[$key]) ? $try[$key] : null;
    }

		// 当前用户是否申请过
		$applied = 0;
    $apply_id = null;
		if($user_id){
      // 是否已想要
      if($try['step_stat']==0){
        $attend_model = new Sher_Core_Model_Attend();
        $is_want = $attend_model->check_signup($user_id, $try['_id'], Sher_Core_Model_Attend::EVENT_TRY_WANT);
        if($is_want){
          $applied = 1;
        }
      }else{  // 是否申请过
        $apply_model = new Sher_Core_Model_Apply();
        $has_one_apply = $apply_model->first(array('target_id'=>$try['_id'], 'user_id'=>$user_id));
        if(!empty($has_one_apply)){
          $applied = 1;
          $apply_id = (string)$has_one_apply['_id'];
        }
      }
    } // endif user


    //转换描述格式
    $data['content_view_url'] = 
    // 封面图url
    $data['cover_url'] = $try['cover']['thumbnails']['aub']['view_url'];
    // banner图url
    $data['banner_url'] = $try['banner']['thumbnails']['aub']['view_url'];

    // 当前用户是否已申请
    $data['applied'] = $applied;
    $data['share_view_url'] = empty($applied) ? null : sprintf('%s/app/api/view/try_show?id=%d', Doggy_Config::$vars['app.domain.base'], $apply_id);
    $data['share_desc'] = empty($applied) ? null : "跪求支持!";

		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 提交申请
	 */
  public function apply(){

    $user_id = $this->current_user_id;

    if(empty($user_id)){
		  return $this->api_json('请先登录！', 3000);
    }

		if (!isset($this->stash['target_id'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['name'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['phone'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['content'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['province'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['district'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['address'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['zip'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['wx'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		if (!isset($this->stash['qq'])){
			return $this->api_json('缺少请求参数！', 3001);
		}
		
		$target_id = (int)$this->stash['target_id'];
		
		try{
			// 验证是否结束
			$try_model = new Sher_Core_Model_Try();
			$try = $try_model->extend_load($target_id);

      // 预热状态不可申请
			if($try['step_stat']==0){
				return $this->api_json('预热中是不能申请的！', 3002);
			}
			if($try['is_end']){
				return $this->api_json('抱歉，活动已结束，等待下次再来！', 3003);
			}
			
			// 检测是否已提交过申请
			$apply_model = new Sher_Core_Model_Apply();
			if(!$apply_model->check_reapply($user_id,$target_id)){
				return $this->api_json('你已提交过申请，无需重复提交！', 3004);
      }

      $data = array(
        'user_id' => $user_id,
        'target_id' => $target_id,
        'type' => 1,
        'from_to' => 2,
        'content' => $this->stash['content'],
        'phone' => $this->stash['phone'],
        'name' => $this->stash['name'],
        'province' => $this->stash['province'],
        'district' => $this->stash['district'],
        'address' => $this->stash['address'],
        'zip' => $this->stash['zip'],
        'wx' => $this->stash['wx'],
        'qq' => $this->stash['qq'],
        'ip' => Sher_Core_Helper_Auth::get_ip(),
      );

      $user_model = new Sher_Core_Model_User();
      $user = $user_model->find_by_id($user_id);
      $data['nickname'] = $user['nickname'];

      // 补全用户信息
      $user_data = array();
      if(empty($user['profile']['realname'])){
        $user_data['profile.realname'] = isset($this->stash['name']) ? $this->stash['name'] : null;
      }
      if(empty($user['profile']['phone'])){
        $user_data['profile.phone'] = isset($this->stash['phone']) ? $this->stash['phone'] : null;
      }
      if(empty($user['profile']['address'])){
        $user_data['profile.address'] = isset($this->stash['address']) ? $this->stash['address'] : null;
      }
      if(empty($user['profile']['zip'])){
        $user_data['profile.zip'] = isset($this->stash['zip']) ? $this->stash['zip'] : null;
      }
      if(empty($user['profile']['weixin'])){
        $user_data['profile.weixin'] = isset($this->stash['wx']) ? $this->stash['wx'] : null;
      }
      if(empty($user['profile']['im_qq'])){
        $user_data['profile.im_qq'] = isset($this->stash['qq']) ? $this->stash['qq'] : null;
      }
      if(empty($user['profile']['province_id'])){
        $user_data['profile.province_id'] = isset($this->stash['province']) ? (int)$this->stash['province'] : 0;
      }
      if(empty($user['profile']['district_id'])){
        $user_data['profile.district_id'] = isset($this->stash['district']) ? (int)$this->stash['district'] : 0;
      }

      //更新基本信息
      $user_model->update_set($user_id, $user_data);

      $ok = $apply_model->apply_and_save($data);
      if($ok){

        //试用显示的字段
        $try_some_fields = array(
          '_id', 'title', 'short_title', 'cover_id', 'banner_id', 'step_stat', 'sticked',
          'tags', 'comment_count', 'created_on', 'kind',
          'try_count', 'apply_count', 'report_count', 'want_count', 'view_count',
          'buy_url', 'open_limit', 'open_limit', 'apply_term', 'term_count',
          'start_time', 'end_time', 'publish_time', 'state', 'price', 'pass_users',
        );

        // 重建数据结果
        $try_data = array();
        for($i=0;$i<count($try_some_fields);$i++){
          $key = $try_some_fields[$i];
          $try_data[$key] = isset($try[$key]) ? $try[$key] : null;
        }

        // 分享拉票
        $share_view_url = sprintf("%s/try/apply_success?apply_id=%s", Doggy_Config::$vars['app.url.wap'], $apply_model->id);

			  return $this->api_json('申请成功！', 0, array('apply_id'=>$apply_model->id, 'try'=>$try_data, 'share_view_url'=>$share_view_url) );
      }else{
				return $this->api_json('申请失败！', 3005);
      }

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->api_json('提交失败，请重试！', 3006);
		}

	}

	
}

