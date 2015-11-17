<?php
/**
 * 试用API接口
 * @author tianshuai
 */
class Sher_Api_Action_Try extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {
	
	public $stash = array(

	);
	
	
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
      '_id'=> 1, 'title'=>1, 'description'=>1, 'content'=>1, 'cover_id'=>1, 'banner_id'=>1, 'step_stat'=>1, 'sticked'=>1,
      'tags'=>1, 'comment_count'=>1, 'created_on'=>1, 'kind'=>1,
      'try_count'=>1, 'apply_count'=>1, 'report_count'=>1, 'want_count'=>1, 'view_count'=>1,
      'buy_url'=>1, 'open_limit'=>1, 'open_limit'=>1, 'apply_term'=>1, 'term_count'=>1,
      'start_time'=>1, 'end_time'=>1, 'publish_time'=>1, 'state'=>1, 'price'=>1, 'user_id'=>1, 'pass_users'=>1,
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
        $c = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
				if($c) $data[$i][$key] = $result['rows'][$i][$key];
			}

      // 过滤用户表
      if(isset($result['rows'][$i]['user'])){
        $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user'], array('symbol_1', 'symbol_2'));
      }

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
		$inc_ran = rand(1, 6);
		$model->increase_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
    if ($this->current_user_id == $try['user_id']){
      $editable = true;
    }

    // 过滤用户表
    if(isset($try['user'])){
      $try['user'] = Sher_Core_Helper_FilterFields::user_list($try['user'], array('symbol_1', 'symbol_2'));
    }

		$result['try'] = &$try;
		$result['editable'] = $editable;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 提交申请
	 */
  public function apply(){

    $user_id = 5;

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
      );

      $user_model = new Sher_Core_Model_User();
      $user = $user_model->find_by_id($user_id);
      $data['nickname'] = $user['nickname'];

      // 补全用户信息
      $user_data = array();
      if(empty($user['profile']['realname'])){
        $user_data['profile.realname'] = isset($this->stash['name']) ? $this->stash['name'] : null;
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

      //更新基本信息
      $user_model->update_set($user_id, $user_data);

      $ok = $apply_model->apply_and_save($data);
      if($ok){
			  return $this->api_json('申请成功！', 0, array('apply_id'=>$apply_model->id) );
      }else{
				return $this->api_json('申请失败！', 3005);
      }

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->api_json('提交失败，请重试！', 3006);
		}

	}

	
}

