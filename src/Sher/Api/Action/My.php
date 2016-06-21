<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_My extends Sher_Api_Action_Base {


	protected $filter_user_method_list = array('set_my_qr_code','add_head_pic');
	
	/**
	 * 入口
	 */
	public function execute(){
		
	}

	/**
	 * 客户端上传Token
	 */
	public function upload_token(){

    Doggy_Log_Helper::warn('api begnin.save img......');
    if(empty($this->stash['tmp'])){
 		  return $this->api_json('请选择图片！', 3001);  
    }
    $file = base64_decode(str_replace(' ', '+', $this->stash['tmp']));
    $image_info = Sher_Core_Util_Image::image_info_binary($file);
    if($image_info['stat']==0){
      return $this->api_json($image_info['msg'], 3002);
    }
    Doggy_Log_Helper::warn($image_info['format']);
    if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
		  return $this->api_json('图片格式不正确！', 3003);
    }
		$type = (int)$this->stash['type'];
    $user_id = $this->current_user_id;
		// 图片上传参数
		$params = array();
    $result = array();
		$new_file_id = new MongoId();
		switch($type){
			case 1:
				$params['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
				$params['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
        $result = array();
				break;
			case 2:
				$domain = Sher_Core_Util_Constant::STROAGE_TOPIC;
				$asset_type = Sher_Core_Model_Asset::TYPE_TOPIC;
				break;
			case 3:
				$params['domain'] = Sher_Core_Util_Constant::STROAGE_AVATAR;
				$params['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;
				$params['parent_id'] = $user_id;
				$params['filename'] = $new_file_id.'.jpg';
				$params['image_info'] = $image_info;
				$result = Sher_Core_Util_Image::api_avatar($file, $params);
				break;
			default:
				$domain = Sher_Core_Util_Constant::STROAGE_ASSET;
				$asset_type = Sher_Core_Model_Asset::TYPE_ASSET;
				break;
		}
    
    if($result['stat']){
      return $this->api_json('上传成功!', 0, $result['asset']);
    }else{
 		  return $this->api_json('上传失败!', 3005); 
    }

	}
	
	/**
	 * 更新用户头像
	 */
	public function update_avatar(){
		$user_id = $this->current_user_id;
		$qkey = (int)$this->stash['qkey'];
		
		$avatar = array(
			'big' => $qkey,
			'medium' => $qkey,
			'small' => $qkey,
			'mini' => $qkey
		);
		
		$user = new Sher_Core_Model_User();
		$ok = $user->update_avatar($avatar, $user_id);
		
		return $this->api_json('更新成功', 0);
	}
	
	/**
	 * 更新用户信息
	 */
	public function update_profile(){
		
		$user_id = $this->current_user_id;

		if(empty($user_id)){
				return $this->api_json('请先登录！', 3000);   
		}
		
		$user_info = array();
		
		$profile = array();
		if(isset($this->stash['job']) && !empty($this->stash['job'])){
		  $data['profile.job'] = $this->stash['job'];
		}
		if(isset($this->stash['company']) && !empty($this->stash['company'])){
		  $data['profile.company'] = $this->stash['company'];
		}
		if(isset($this->stash['phone']) && !empty($this->stash['phone'])){
		  $data['profile.phone'] = $this->stash['phone'];
		}
		if(isset($this->stash['address']) && !empty($this->stash['address'])){
		  $data['profile.address'] = $this->stash['address'];
		}
		if(isset($this->stash['realname']) && !empty($this->stash['realname'])){
		  $data['profile.realname'] = $this->stash['realname'];
		}
		if(isset($this->stash['province_id']) && !empty($this->stash['province_id'])){
		  $data['profile.province_id'] = (int)$this->stash['province_id'];
		}
		if(isset($this->stash['district_id']) && !empty($this->stash['district_id'])){
		  $data['profile.district_id'] = (int)$this->stash['district_id'];
		}
		if(isset($this->stash['zip']) && !empty($this->stash['zip'])){
		  $data['profile.zip'] = $this->stash['zip'];
		}
		if(isset($this->stash['im_qq']) && !empty($this->stash['im_qq'])){
		  $data['profile.im_qq'] = $this->stash['im_qq'];
		}
		if(isset($this->stash['label']) && !empty($this->stash['label'])){
		  $data['profile.label'] = $this->stash['label'];
		}
		if(isset($this->stash['weixin']) && !empty($this->stash['weixin'])){
		  $data['profile.weixin'] = $this->stash['weixin'];
		}
		if(isset($this->stash['birthday']) && !empty($this->stash['birthday'])){
		  $age_arr = explode('-', $this->stash['birthday']);
		  $data['profile.age'] = $age_arr;
		}
			
		if(isset($this->stash['nickname']) && !empty($this->stash['nickname'])){
		  $data['nickname'] = (int)$this->stash['nickname'];
		}
		if(isset($this->stash['sex'])){
		  $data['sex'] = (int)$this->stash['sex'];
		}
		if(isset($this->stash['city']) && !empty($this->stash['city'])){
		  $data['city'] = $this->stash['city'];
		}
		if(isset($this->stash['email']) && !empty($this->stash['email'])){
		  $data['email'] = $this->stash['email'];
		}
		if(isset($this->stash['summary']) && !empty($this->stash['summary'])){
		  $data['summary'] = $this->stash['summary'];
		}
		
		try {
			$user = new Sher_Core_Model_User();
			
      if(isset($this->stash['nickname'])){

        if (strlen($this->stash['nickname'])<4 || strlen($this->stash['nickname'])>30) {
          return $this->api_json('昵称长度大于等于4个字符，小于30个字符，每个汉字占3个字符！', 3002);
        }

        // 检测用户昵称是否唯一
        if(!$user->_check_name($this->stash['nickname'], $user_id)){
          return $this->api_json('用户昵称已被占用！', 3003);
        }
			  $data['nickname'] = $this->stash['nickname'];
      }
			
			$ok = $user->update_set($user_id, $data);
      if($ok){
        $user_data = $user->load($user_id);
        // 过滤用户字段
        $user_data = $user->extended_model_row($user_data);
        $data = Sher_Core_Helper_FilterFields::wap_user($user_data);

 		    return $this->api_json('更新用户信息成功！', 0, $data);    
      }else{
  		  return $this->api_json('更新失败！', 3004);    
      }
			
		} catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to update profile:'.$e->getMessage());
            return $this->api_json("更新失败:".$e->getMessage(), 4001);
    }
	}
	
	/**
	 * 我的收藏
	 */
	public function favorite(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$type = isset($this->stash['type'])?(int)$this->stash['type']:1;
		$event = isset($this->stash['event'])?(int)$this->stash['event']:1;
		$user_id = $this->current_user_id;
		if(!in_array($type, array(1,2))){
			return $this->api_json('请求参数不匹配！', 3000);
		}
		if(!in_array($type, array(1,2,3))){
			return $this->api_json('请求参数不匹配！', 3001);
		}
		
		if($type == 1){ // 产品
			$some_fields = array(
        '_id'=>1, 'title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1, 'cover_id'=>1, 'favorite_count'=>1,
        'designer_id'=>1, 'category_id'=>1, 'stage'=>1, 'love_count'=>1,'view_count'=>1, 'summary'=>1, 'comment_count'=>1,
			);
		}elseif($type == 2){  // 话题
			$some_fields = array(
				'_id'=>1, 'title'=>1, 'created_on'=>1, 'comment_count'=>1,'user_id'=>1,
			);
    }else{
      $some_fields = array();
    }
		
		$query = array();
		$options = array();
		
		// 查询条件
		if($user_id){
			$query['user_id'] = $user_id;
		}
		if($type){
			$query['type'] = $type;
		}
    if($event){
		  $query['event'] = $event;
    }
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;
		$options['sort_field'] = 'time';
		
		// 开启查询
    $service = Sher_Core_Service_Favorite::instance();
    $result = $service->get_like_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
      $data[$i]['_id'] = $result['rows'][$i]['_id'];
      $data[$i]['type'] = $result['rows'][$i]['type'];
      $data[$i]['event'] = $result['rows'][$i]['event'];
      $data[$i]['target_id'] = $result['rows'][$i]['target_id'];
			if($type == 1){
        foreach($some_fields as $key=>$value){
          $data[$i]['product'][$key] = $result['rows'][$i]['product'][$key];
        }
        //$product = new Sher_Core_Model_Product();
        // 获取商品价格区间
        //$data[$i]['product']['range_price'] = $product->range_price($result['rows'][$i]['product']['_id'], $result['rows'][$i]['product']['stage']);
        
        // 封面图url
        $data[$i]['product']['cover_url'] = $result['rows'][$i]['product']['cover']['thumbnails']['medium']['view_url'];
			}
			if($type == 2){
				foreach($some_fields as $key=>$value){
					$data[$i]['topic'][$key] = $result['rows'][$i]['topic'][$key];
          $data[$i]['topic']['content_view_url'] = sprintf('%s/app/site/topic/api_view?id=%d', Doggy_Config::$vars['app.domain.base'], $result['rows'][$i]['topic']['_id']);
				}
			}
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 取消订单
	 */
	public function cancel_order(){
		$rid = $this->stash['rid'];
    $user_id = $this->current_user_id;
		if (empty($rid)) {
			return $this->api_json('缺少请求参数！', 3000);
		}
		if (empty($user_id)) {
			return $this->api_json('请先登录！', 3001);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
    //订单不存在
    if(empty($order_info)){
 			return $this->api_json('订单不存在！', 3002);   
    }
		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
 			return $this->api_json('该订单出现异常，请联系客服！', 3003);   
		}
		try {
			// 关闭订单
			$model->canceled_order($order_info['_id'], array('user_id'=>$order_info['user_id']));
    } catch (Sher_Core_Model_Exception $e) {
 		  return $this->api_json('取消订单失败:'.$e->getMessage(), 3004);   
    }
		
 		return $this->api_json('操作成功！', 0, array('rid'=> $rid)); 
	}

	/**
	 * 修改密码
	 */
	public function modify_password(){

    $user_id = $this->current_user_id;
    if (empty($this->stash['password']) || empty($this->stash['new_password'])) {
        return $this->api_json('参数不完整!', 3001);
    }

    if (strlen($this->stash['password'])<6 || strlen($this->stash['new_password'])<6) {
        return $this->api_json('密码要大于6位', 3002);
    }
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->load($user_id);
    if (empty($user)) {
        return $this->api_json('帐号不存在!', 3003);
    }
    if ($user['password'] != sha1($this->stash['password'])) {
        return $this->api_json('原密码不正确!', 3004);
    }
    $nickname = $user['nickname'];
    $user_state = $user['state'];
    
    if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
        return $this->api_json('此帐号涉嫌违规已经被锁定!', 3005);
    }
    if ($user_state == Sher_Core_Model_User::STATE_DISABLED) {
        return $this->api_json('此帐号涉嫌违规已经被禁用!', 3006);
    }

    // 更新密码
    $ok = $user_model->update_set($user_id, array('password'=>sha1($this->stash['new_password'])));
    if(!$ok){
      return $this->api_json('更新密码失败!', 3007);   
    }

		$user_data = $user_model->extended_model_row($user);
    // 过滤用户字段
    $data = Sher_Core_Helper_FilterFields::wap_user($user_data);
		
		return $this->api_json('修改成功!', 0, $data);
	}

  /**
   * 我的红包列表
   */
  public function bonus(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    }
 		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
    $size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		$sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0; 
		$used = isset($this->stash['used'])?(int)$this->stash['used']:0; 
		$is_expired = isset($this->stash['is_expired'])?(int)$this->stash['is_expired']:0; 

		$some_fields = array(

		);
			
		$query   = array();
		$options = array();
		
		// 查询条件
		$query['user_id'] = $user_id;
    if($used){
      $query['used'] = $used;
    }
    if($is_expired){
      if($is_expired==1){ // 未过期
        $query['expired_at'] = array('$gt'=>time());
      }elseif($is_expired==2){  // 已过期
        $query['expired_at'] = array('$lt'=>time());     
      }
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
    $service = Sher_Core_Service_Bonus::instance();
    $result = $service->get_all_list($query, $options);
		
		return $this->api_json('请求成功', 0, $result);
  
  }

  /**
   * 验证用户是否签到过
   */
  public function check_user_sign(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    }
    $has_sign = $continuity_times = 0;
    $user_sign_model = new Sher_Core_Model_UserSign();
    $user_sign = $user_sign_model->load($user_id);

    if($user_sign){
      $today = (int)date('Ymd');
      $yesterday = (int)date('Ymd', strtotime('-1 day'));
      if($user_sign['last_date'] == $yesterday){
        $continuity_times = $user_sign['sign_times'];
      }elseif($user_sign['last_date'] == $today){
        $has_sign = 1;
        $continuity_times = $user_sign['sign_times'];
      }
    }
    $result = array('has_sign'=>$has_sign, 'continuity_times'=>$continuity_times);
 		return $this->api_json('请求成功', 0, $result);
  }

  /**
   * 执行签到操作
   */
  public function user_sign(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    }
    $data = array();
    $user_sign_model = new Sher_Core_Model_UserSign();
    $result = $user_sign_model->sign_in($user_id, array());
    if(empty($result['is_true'])){
      // code 3005 是已经签到过了
      return $this->api_json($result['msg'], $result['code']);    
    }else{
      $data['continuity_times'] = $result['continuity_times'];
      $data['give_money'] = $result['give_money'];
    }
 		return $this->api_json($result['msg'], 0, $data);
  }

  /**
   * 删除订单
   */
  public function delete_order(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    }

    $rid = isset($this->stash['rid'])?$this->stash['rid']:null;
    if(empty($rid)){
      return $this->api_json('缺少请求参数!', 3001);   
    }

    $order_model = new Sher_Core_Model_Orders();
    $order = $order_model->find_by_rid((string)$rid);
    if(empty($order)){
      return $this->api_json('订单不存在!', 3002);   
    }

    if($order['user_id'] != $user_id){
      return $this->api_json('没有权限!', 3003);   
    }

    // 允许删除订单状态数组
    $allow_stat_arr = array(
      Sher_Core_Util_Constant::ORDER_EXPIRED,
      Sher_Core_Util_Constant::ORDER_CANCELED,
      Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
      //Sher_Core_Util_Constant::ORDER_EVALUATE,
      Sher_Core_Util_Constant::ORDER_PUBLISHED,
      Sher_Core_Util_Constant::ORDER_REFUND_DONE,
    );
    if(!in_array($order['status'], $allow_stat_arr)){
      return $this->api_json('该订单状态不允许删除!', 3004);     
    }

    $ok = $order_model->update_set((string)$order['_id'], array('deleted'=>1));
    if($ok){
      return $this->api_json('操作成功!', 0, array('rid'=>$order['rid']));       
    }else{
      return $this->api_json('订单删除失败!', 3005);
    }

  }

  /**
   * 我最近使用的标签
   */
  public function my_recent_tags(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    } 
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $model = new Sher_Core_Model_UserTags();
    $tags = $model->load($user_id);
    if(empty($tags)){
      return $this->api_json('标签不存在!', 0, array('has_tag'=>0));
    }
    $tag_arr = array();
    switch($type){
      case 1:
        $field = 'scene_tags';
        $tag_arr = $tags[$field];
        break;
      default:
        $tag_arr = array();
    }
    if(empty($tag_arr)){
      return $this->api_json('标签不存在', 0, array('has_tag'=>0));   
    }

    $items = array();
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $tag_arr = array_reverse($tag_arr);
    // 取前30个标签
    $tag_arr = array_slice($tag_arr, 0, 30);
    foreach($tag_arr as $v){
      $obj = $scene_tags_model->load((int)$v);
      if($obj){
        array_push($items, $obj);
      }else{  // 清除不存在的标签
        $model->remove_item_custom($user_id, $field, $v);
      }
    }

    if(empty($items)){
      return $this->api_json('标签不存在', 0, array('has_tag'=>0));   
    }

    return $this->api_json('success', 0, array('has_tag'=>1, 'tags'=>$items)); 
  
  }

  public function update_user_identify(){
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);    
    }
    if(empty($type)){
      return $this->api_json('请选择要更新的类型!', 3001);    
    }
    $field = null;
    if($type==1){
      $field = 'is_scene_subscribe';
    }else{
      return $this->api_json('更新的类型错误!', 3002);    
    }
    $user_model = new Sher_Core_Model_User();
    $ok = $user_model->update_user_identify($user_id, $field, 1);
    if($ok){
      return $this->api_json('操作成功!', 0, array());    
    }else{
      return $this->api_json('更新失败!', 3003);    
    }

  }

  /**
   * 送红包
   */
  public function send_bonus(){
    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录!', 3000);
    }
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
    if($type==1){
      if(empty($rid)){
        return $this->api_json('缺少请求参数!', 3004);      
      }
      $orders_model = new Sher_Core_Model_Orders();
      $order = $orders_model->find_by_rid($rid);
      
      //订单不存在
      if(empty($order)){
        return $this->api_json('订单不存在！', 3005);   
      }
      // 是否待发货订单
      if ($order['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
        return $this->api_json('订单状态不正确！', 3006);   
      }

      $row = array(
        'count' => 5,
        'xname' => 'AS',
        'bonus' => 'E',
        'min_amounts' => 'C',
      );
    
    }else{
      return $this->api_json('类型参数不正确!', 3001);     
    }

    switch($row['bonus']){
    case 'A':
      $bonus_money = 50;
      break;
    case 'B':
      $bonus_money = 100;
      break;
    case 'C':
      $bonus_money = 30;
      break;
    case 'D':
      $bonus_money = 52;
      break;
    case 'E':
      $bonus_money = 5;
      break;
    default:
      $bonus_money = 0;
    }

    if($bonus_money==0){
      return $this->api_json('系统内部错误!', 3002);   
    }

    $ok = Sher_Core_Util_Shopping::give_bonus((int)$user_id, $row);
    if(!$ok){
      return $this->api_json('发送红包失败!', 3003);     
    }
    return $this->api_json('success!', 0, array('xname'=>$row['xname'], 'bonus_money'=>$bonus_money));
  }
	
	/**
   * 达人认证
   */
	public function talent_save(){
		$data = array();
		$user_id = $this->current_user_id;

		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$info = isset($this->stash['info']) ? $this->stash['info'] : null;
		$contact = isset($this->stash['contact']) ? $this->stash['contact'] : null;
		$label = isset($this->stash['label']) ? $this->stash['label'] : null;
		if(!$info || !$contact || !$label){
			return $this->api_json('缺少请求参数!', 3000);    
		}

		$data['info'] = $info;
		$data['contact'] = $contact;
		$data['label'] = $label;
		
		// 上传身份证照片
		if(isset($this->stash['id_card_a_tmp']) && !empty($this->stash['id_card_a_tmp'])){
      $file = base64_decode(str_replace(' ', '+', $this->stash['id_card_a_tmp']));
      $image_info = Sher_Core_Util_Image::image_info_binary($file);
      if($image_info['stat']==0){
        return $this->api_json($image_info['msg'], 3002);
      }
      if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
        return $this->api_json('图片格式不正确！', 3003);
      }
      $params = array();
      $new_file_id = Sher_Core_Helper_Util::generate_mongo_id();
      $params['domain'] = Sher_Core_Util_Constant::STROAGE_ID_CARD;
      $params['asset_type'] = Sher_Core_Model_Asset::TYPE_ID_CARD;
      $params['filename'] = $new_file_id.'.jpg';
      $params['parent_id'] = $user_id;
      $params['image_info'] = $image_info;
      $result = Sher_Core_Util_Image::api_image($file, $params);
      
      if($result['stat']){
        $data['id_card_cover_id'] = (string)$result['asset']['id'];
      }else{
        return $this->api_json('上传失败!', 3005); 
      }

		}
		
		// 上传名片图片
		if(isset($this->stash['business_card_tmp']) && !empty($this->stash['business_card_tmp'])){
      $file = base64_decode(str_replace(' ', '+', $this->stash['business_card_tmp']));
      $image_info = Sher_Core_Util_Image::image_info_binary($file);
      if($image_info['stat']==0){
        return $this->api_json($image_info['msg'], 3002);
      }
      if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
        return $this->api_json('图片格式不正确！', 3008);
      }
      $params = array();
      $new_file_id = Sher_Core_Helper_Util::generate_mongo_id();
      $params['domain'] = Sher_Core_Util_Constant::STROAGE_BUSINESS_CARD;
      $params['asset_type'] = Sher_Core_Model_Asset::TYPE_BUSINESS_CARD;
      $params['filename'] = $new_file_id.'.jpg';
      $params['parent_id'] = $id;
      $params['user_id'] = $user_id;
      $params['image_info'] = $image_info;
      $result = Sher_Core_Util_Image::api_image($file, $params);
      
      if($result['stat']){
        $data['business_card_cover_id'] = (string)$result['asset']['id'];
      }else{
        return $this->api_json('上传失败!', 3005); 
      }

		}
		
		try{
      $model = new Sher_Core_Model_UserTalent();
      if(empty($id)){
        $has_one = $model->first(array('user_id'=>$user_id));
        if($has_one){
          return $this->api_json('不能重复提交!', 4003);       
        }
        $mode = 'create';
		    $data['user_id'] = $user_id;
			  $ok = $model->apply_and_save($data);
        $res = $model->get_data();
        $id = (string)$res['_id'];
      }else{
        $mode = 'update';
        $data['_id'] = $id;
        $data['verified'] = 0;
        $ok = $model->apply_and_update($data);
      }
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}

      // 更新图片信息
      if($mode=='create'){
        if(isset($data['id_card_cover_id']) && !empty($data['id_card_cover_id'])){
          $model->update_batch_assets($data['id_card_cover_id'], $id);
        }
        if(isset($data['business_card_cover_id']) && !empty($data['business_card_cover_id'])){
          $model->update_batch_assets($data['business_card_cover_id'], $id);
        }     
      }

		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}

	/**
   * 获取达人认证
   */
	public function fetch_talent(){
		$data = array();
		$user_id = $this->current_user_id;

		$model = new Sher_Core_Model_UserTalent();	
    $talent = $model->first(array('user_id'=>$user_id));
    if(empty($talent)){
		  return $this->api_json('用户未申请过', 0, array('verified'=>-1));
    }else{
      $talent = $model->extended_model_row(&$talent);
      $talent['_id'] = (string)$talent['_id'];
      unset($talent['user']);
      if(isset($talent['id_card_cover'])){
        $talent['id_card_cover_url'] = $talent['id_card_cover']['thumbnails']['hd']['view_url'];
        unset($talent['id_card_cover']);
      }else{
        $talent['id_card_cover_url'] = null;
      }

      if(isset($talent['business_card_cover'])){
        $talent['business_card_cover_url'] = $talent['business_card_cover']['thumbnails']['hd']['view_url'];
        unset($talent['business_card_cover']);
      }else{
        $talent['business_card_cover_url'] = null;
      }
    }
		return $this->api_json('success', 0, $talent);
	}

	
	/**
	* 获取用户二维码
	*/
	public function set_my_qr_code(){
		
		header("content-type: image/png");
		$user_id = $this->current_user_id;
		//$user_id = 10;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
		
		$home_url = Doggy_Config::$vars['app.url.user'].'/'.$user_id;

		$qrCode = new Endroid\QrCode\QrCode();
		$qrCode
			->setText($home_url)
			->setSize($size)
			//->setExtension('jpg')
			//->setLogo('http://frbird.qiniudn.com/avatar/160328/56f90f9916c149af077f5909-avb.jpg')
            //->setLogoSize(48)
			->setPadding(10)
			->setErrorCorrection('high')
			->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
			->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
			//->setLabel('My label')
			//->setLabelFontSize(16)
			->render()
		;
	}
	
	/*
	 * 添加用户信息头图
	 */
	public function add_head_pic(){
		
		$user_id = $this->current_user_id;
		//$user_id = 10;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		$data = array();
		$data['_id'] = (int)$user_id;
		//$this->stash['tmp'] = Doggy_Config::$vars['app.imges'];
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
		$params['domain'] = Sher_Core_Util_Constant::STROAGE_USER_HEAD_PIC;
		$params['asset_type'] = Sher_Core_Model_Asset::TYPE_USER_HEAD_PIC;
		$params['filename'] = $new_file_id.'.jpg';
		$params['parent_id'] = (int)$user_id;
		$params['user_id'] = (int)$user_id;
		$params['image_info'] = $image_info;
		$result = Sher_Core_Util_Image::api_image($file, $params);
		
		if($result['stat']){
			$data['head_pic'] = $result['asset']['id'];
		}else{
			return $this->api_json('上传失败!', 3005); 
		}
		
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_User();
			$ok = $model->apply_and_update($data);
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			
			$asset_model = new Sher_Core_Model_Asset();
			$asset = $asset_model->extend_load($data['head_pic']);
			if($asset){
				$data['head_pic_url'] = $asset['thumbnails']['huge']['view_url'];
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('保存失败:'.$e->getMessage(), 4002);
		}
		
		return $this->api_json('提交成功', 0, array('head_pic_url'=>$data['head_pic_url']));
	}

  /**
   * 评论我的列表
   */
	public function comment_list(){

    $user_id = $this->current_user_id;
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		// 请求参数
		$target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		$target_user_id = isset($this->stash['target_user_id']) ? (int)$this->stash['target_user_id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 12;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 1;
		$deleted = isset($this->stash['deleted']) ? (int)$this->stash['deleted'] : -1;
		
		if(empty($type)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		 
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'user_id'=>1, 'content'=>1, 'star'=>1, 'target_id'=>1, 'target_user_id'=>1, 'sku_id'=>1,
		  'deleted'=>1, 'reply_user_id'=>1, 'floor'=>1, 'type'=>1, 'sub_type'=>1, 'user'=>1, 'target_user'=>1,
		  'love_count'=>1, 'invented_love_count'=>1, 'is_reply'=>1, 'reply_id'=>1, 'created_on'=>1, 'updated_on'=>1,
		  'reply_comment'=>1,
		);
		
		// 查询条件
		if ($target_user_id) {
			$query['target_user_id'] = (int)$user_id;
		}
		
		if ($target_id) {
			$query['target_id'] = (string)$target_id;
		}
		
		if ($type) {
			$query['type'] = (int)$type;
		}

    if($deleted){
      if($deleted==-1){
        $query['deleted'] = 0;
      }else{
        $query['deleted'] = 1;
      }
    }
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'earliest';
				break;
			case 1:
				$options['sort_field'] = 'latest';
				break;
			case 2:
				$options['sort_field'] = 'hotest';
				break;
		}

		// 开启查询
		$service = Sher_Core_Service_Comment::instance();
		$result = $service->get_comment_list($query,$options);

    $scene_sight_model = new Sher_Core_Model_SceneSight();

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$val){
					  $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
				  }
			$data[$i]['_id'] = (string)$data[$i]['_id'];
			if($data[$i]['user']){
			  $data[$i]['user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['user']);
			}
			if($data[$i]['target_user']){
			  $data[$i]['target_user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['target_user']);
			}
      if($data[$i]['reply_comment']){
        $data[$i]['reply_user_nickname'] = $data[$i]['reply_comment']['user']['nickname'];
        //$data[$i]['reply_comment']['user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['reply_comment']['user']);
      }else{
        $data[$i]['reply_user_nickname'] = null;
      }
      $data[$i]['created_at'] = Sher_Core_Helper_Util::relative_datetime($data[$i]['created_on']);

      // 场景信息
      $data[$i]['target_small_cover_url'] = null;
      $data[$i]['target_title'] = null;
      if($data[$i]['type']==Sher_Core_Model_Comment::TYPE_SCENE_SIGHT){
        $scene_sight = $scene_sight_model->extend_load((int)$data[$i]['target_id']);
        if(!empty($scene_sight)){
          $data[$i]['target_title'] = $scene_sight['title'];
          $data[$i]['target_small_cover_url'] = $scene_sight['cover']['thumbnails']['mini']['view_url'];
        }
      }
		}
		$result['rows'] = $data;

    //清空评论提醒数量
    if($page==1){
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->load($user_id);
      if($user && isset($user['counter']['fiu_comment_count']) && $user['counter']['fiu_comment_count']>0){
        $user_model->update_counter($user_id, 'fiu_comment_count');
      }
    }

		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 列表
	 */
	public function remind_list(){
		
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;

    $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'user_id'=>1, 's_user_id'=>1, 'b_readed'=>1, 'readed'=>1, 'kind'=>1, 'content'=>1, 'parent_related_id'=>1, 'evt'=>1,
		  'user'=>1, 's_user'=>1, 'target'=>1, 'kind_str'=>1, 'related_id'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		// 查询条件
		$query['user_id'] = $user_id;
		if($type == 1){
			$query['kind'] = array('$in'=>array(Sher_Core_Model_Remind::KIND_SCENE, Sher_Core_Model_Remind::KIND_SIGHT));
		}
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'time';
				break;
		}
		
		// 开启查询
		$service = Sher_Core_Service_Remind::instance();
		$result = $service->get_remind_list($query,$options);
		$user_model = new Sher_Core_Model_User();
    $remind_model = new Sher_Core_Model_Remind();
		
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
      $result['rows'][$k]['s_user'] = Sher_Core_Helper_FilterFields::wap_user($result['rows'][$k]['s_user']);
      $result['rows'][$k]['user'] = Sher_Core_Helper_FilterFields::wap_user($result['rows'][$k]['user']);

      if(!isset($result['rows'][$k]['target']) && empty($result['rows'][$k]['target'])){
        continue;
      }
      $result['rows'][$k]['target_title'] = $result['rows'][$k]['target']['title'];

      if($result['rows'][$k]['kind']==Sher_Core_Model_Remind::KIND_SCENE){  // 情景
        $result['rows'][$k]['target_cover_url'] = $result['rows'][$k]['target']['cover']['thumbnails']['mini']['view_url'];
      }elseif($result['rows'][$k]['kind']==Sher_Core_Model_Remind::KIND_SIGHT){ // 场景
        $result['rows'][$k]['target_cover_url'] = $result['rows'][$k]['target']['cover']['thumbnails']['mini']['view_url'];
      }

      $is_read = isset($result['rows'][$k]['readed'])?$result['rows'][$k]['readed']:0;
      $result['rows'][$k]['is_read'] = $is_read;
      if(empty($is_read)){
        # 更新已读标识
        $remind_model->set_readed((string)$result['rows'][$k]['_id']);
      }
		}
		
		// 过滤多余属性
    $filter_fields  = array('target', '__extend__');
    $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);

    //清空提醒数量
    if($page==1){
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->load($user_id);
      if($user && isset($user['counter']['fiu_alert_count']) && $user['counter']['fiu_alert_count']>0){
        $user_model->update_counter($user_id, 'fiu_alert_count');
      }
    }
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}

  /**
   * 我的订阅
   */
  public function my_subscription(){
    $user_id = $this->current_user_id;
    $favorite_model = new Sher_Core_Model_Favorite();
    $scene_ids = array();
    $favorites = $favorite_model->find(
      array(
        'user_id'=>$user_id,
        'event'=>Sher_Core_Model_Favorite::EVENT_SUBSCRIPTION,
        'type'=>Sher_Core_Model_Favorite::TYPE_APP_SCENE_SCENE,
      ),
      array(
        'page'=>1,
        'size'=>50,
        'sort'=>array('created_on'=>-1),
      )
    );
    if(empty($favorites)){
      return $this->api_json('empty', 0, array("total_rows"=>0,"rows"=>array(),"total_page"=>0,"current_page"=>1,"pager"=>"","next_page"=>0,"prev_page"=>0));
    }
    for($i=0;$i<count($favorites);$i++){
      array_push($scene_ids, (int)$favorites[$i]['target_id']);
    }
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'title'=>1, 'des'=>1, 'scene_id'=>1, 'tags'=>1,
			'product' => 1, 'location'=>1, 'address'=>1, 'cover_id'=>1,
			'used_count'=>1, 'view_count'=>1, 'love_count'=>1, 'comment_count'=>1,
			'fine' => 1, 'stick'=>1, 'is_check'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);

    $query = array(
      'scene_id' => array('$in' => $scene_ids),
      'is_check' => 1,
    );
    $options = array(
      'page' => $page,
      'size' => $size,
      'sort_field' => 'latest',
      'some_fields' => $some_fields,
    );

		// 开启查询
    $service = Sher_Core_Service_SceneSight::instance();
    $result = $service->get_scene_sight_list($query, $options);


		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
			
			$user = array();
			
			if($v['user']){
				$user['user_id'] = $v['user']['_id'];
				$user['nickname'] = $v['user']['nickname'];
				$user['avatar_url'] = $v['user']['medium_avatar_url'];
				$user['summary'] = $v['user']['summary'];
				$user['is_expert'] = isset($v['user']['identify']['is_expert']) ? (int)$v['user']['identify']['is_expert'] : 0;
				$user['label'] = isset($v['user']['profile']['label']) ? $v['user']['profile']['label'] : '';
				$user['expert_label'] = isset($v['user']['profile']['expert_label']) ? $v['user']['profile']['expert_label'] : '';
				$user['expert_info'] = isset($v['user']['profile']['expert_info']) ? $v['user']['profile']['expert_info'] : '';
		  }
			
			$result['rows'][$k]['scene_title'] = '';
			if($result['rows'][$k]['scene']){
				$result['rows'][$k]['scene_title'] = $v['scene']['title'];
			}
			
			$result['rows'][$k]['user_info'] = $user;
		}
		// 过滤多余属性
    $filter_fields  = array('scene','cover','user','cover_id','__extend__');
    $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		return $this->api_json('请求成功', 0, $result);
  }


}

