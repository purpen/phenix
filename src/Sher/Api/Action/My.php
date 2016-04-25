<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_My extends Sher_Api_Action_Base {


	protected $filter_user_method_list = array('talent_save','set_my_qr_code');
	
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
      $profile['job'] = $this->stash['job'];
    }
    if(isset($this->stash['company']) && !empty($this->stash['company'])){
      $profile['company'] = $this->stash['company'];
    }
    if(isset($this->stash['phone']) && !empty($this->stash['phone'])){
      $profile['phone'] = $this->stash['phone'];
    }
    if(isset($this->stash['address']) && !empty($this->stash['address'])){
      $profile['address'] = $this->stash['address'];
    }
    if(isset($this->stash['realname']) && !empty($this->stash['realname'])){
      $profile['realname'] = $this->stash['realname'];
    }
    if(isset($this->stash['province_id']) && !empty($this->stash['province_id'])){
      $profile['province_id'] = (int)$this->stash['province_id'];
    }
    if(isset($this->stash['district_id']) && !empty($this->stash['district_id'])){
      $profile['district_id'] = (int)$this->stash['district_id'];
    }
    if(isset($this->stash['zip']) && !empty($this->stash['zip'])){
      $profile['zip'] = $this->stash['zip'];
    }
    if(isset($this->stash['im_qq']) && !empty($this->stash['im_qq'])){
      $profile['im_qq'] = $this->stash['im_qq'];
    }
    if(isset($this->stash['weixin']) && !empty($this->stash['weixin'])){
      $profile['weixin'] = $this->stash['weixin'];
    }
    if(isset($this->stash['birthday']) && !empty($this->stash['birthday'])){
      $age_arr = explode('-', $this->stash['birthday']);
      $profile['age'] = $age_arr;
    }
		
    if(!empty($profile)){
		  $user_info['profile'] = $profile;
    }
		
    if(isset($this->stash['nickname']) && !empty($this->stash['nickname'])){
      $user_info['nickname'] = (int)$this->stash['nickname'];
    }
    if(isset($this->stash['sex'])){
      $user_info['sex'] = (int)$this->stash['sex'];
    }
    if(isset($this->stash['city']) && !empty($this->stash['city'])){
      $user_info['city'] = $this->stash['city'];
    }
    if(isset($this->stash['email']) && !empty($this->stash['email'])){
      $user_info['email'] = $this->stash['email'];
    }
    if(isset($this->stash['summary']) && !empty($this->stash['summary'])){
      $user_info['summary'] = $this->stash['summary'];
    }

    if(empty($user_info)){
   		return $this->api_json('请求参数不能为空！', 3001);    
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
			  $user_info['nickname'] = $this->stash['nickname'];
      }

	    //更新基本信息
			$user_info['_id'] = $user_id;
			
			$ok = $user->apply_and_update($user_info);
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
			$model->canceled_order($order_info['_id']);
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

    // 允许关闭订单状态数组
    $allow_stat_arr = array(
      Sher_Core_Util_Constant::ORDER_EXPIRED,
      Sher_Core_Util_Constant::ORDER_CANCELED,
      Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
      Sher_Core_Util_Constant::ORDER_EVALUATE,
      Sher_Core_Util_Constant::ORDER_PUBLISHED,
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
      return $this->api_json('更新的类型错误!', 0, array());    
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
		// info=test&contact=123456
		$data = array();
		$user_id = $this->current_user_id;
		//$user_id = 10;
		
		if(empty($user_id)){
			return $this->api_json('请先登录!', 3000);    
		}
		$data['user_id'] = $user_id;
		
		$info = isset($this->stash['info']) ? $this->stash['info'] : '';
		if(!$info){
			return $this->api_json('参数不能为空!', 3000);    
		}
		$data['info'] = $info;
		
		$contact = isset($this->stash['contact']) ? $this->stash['contact'] : '';
		if(!$contact){
			return $this->api_json('参数不能为空!', 3000);    
		}
		$data['contact'] = $contact;
		
		// 上传身份证照片
		//$this->stash['id_card_tmp'] = Doggy_Config::$vars['app.imges'];
		if(!isset($this->stash['id_card_tmp']) && empty($this->stash['id_card_tmp'])){
			return $this->api_json('请选择图片！', 3001);  
		}
		$file = base64_decode(str_replace(' ', '+', $this->stash['id_card_tmp']));
		$image_info = Sher_Core_Util_Image::image_info_binary($file);
		if($image_info['stat']==0){
			return $this->api_json($image_info['msg'], 3002);
		}
		if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
			return $this->api_json('图片格式不正确！', 3003);
		}
		$params = array();
		$new_file_id = new MongoId();
		$params['domain'] = Sher_Core_Util_Constant::STROAGE_ID_CARD;
		$params['asset_type'] = Sher_Core_Model_Asset::TYPE_ID_CARD;
		$params['filename'] = $new_file_id.'.jpg';
		$params['parent_id'] = 0;
		$params['image_info'] = $image_info;
		$result = Sher_Core_Util_Image::api_image($file, $params);
		
		if($result['stat']){
			$data['id_card_cover_id'] = $result['asset']['id'];
		}else{
			return $this->api_json('上传失败!', 3005); 
		}
		
		// 上传名片图片
		//$this->stash['business_card_tmp'] = Doggy_Config::$vars['app.imges'];
		if(!isset($this->stash['business_card_tmp']) && empty($this->stash['business_card_tmp'])){
			return $this->api_json('请选择图片！', 3001);  
		}
		$file = base64_decode(str_replace(' ', '+', $this->stash['business_card_tmp']));
		$image_info = Sher_Core_Util_Image::image_info_binary($file);
		if($image_info['stat']==0){
			return $this->api_json($image_info['msg'], 3002);
		}
		if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
			return $this->api_json('图片格式不正确！', 3003);
		}
		$params = array();
		$new_file_id = new MongoId();
		$params['domain'] = Sher_Core_Util_Constant::STROAGE_BUSINESS_CARD;
		$params['asset_type'] = Sher_Core_Model_Asset::TYPE_BUSINESS_CARD;
		$params['filename'] = $new_file_id.'.jpg';
		$params['parent_id'] = 0;
		$params['image_info'] = $image_info;
		$result = Sher_Core_Util_Image::api_image($file, $params);
		
		if($result['stat']){
			$data['business_card_cover_id'] = $result['asset']['id'];
		}else{
			return $this->api_json('上传失败!', 3005); 
		}
		
		try{
			$model = new Sher_Core_Model_UserTalent();	
			$ok = $model->apply_and_save($data);
			$res = $model->get_data();
			$id = $res['_id'];
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			if(isset($data['cover_id']) && !empty($data['cover_id'])){
				$model->update_batch_assets($data['cover_id'], $id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, null);
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
}

