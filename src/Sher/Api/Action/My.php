<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_My extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {
	public $stash = array(

	);
	
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
    if(isset($this->stash['sex']) && !empty($this->stash['sex'])){
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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:5;
		
		$type = isset($this->stash['type'])?(int)$this->stash['type']:1;
		$user_id = $this->current_user_id;
		if(!in_array($type, array(1,2))){
			return $this->api_json('请求参数不匹配！', 3000);
		}
		
		if($type == 1){
			$some_fields = array(
				'_id'=>1, 'title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1, 'presale_people'=>1,
				'presale_percent'=>1, 'cover_id'=>1, 'designer_id'=>1, 'category_id'=>1, 'stage'=>1, 'vote_favor_count'=>1,
				'vote_oppose_count'=>1, 'summary'=>1, 'succeed'=>1, 'voted_finish_time'=>1, 'presale_finish_time'=>1,
				'snatched_time'=>1, 'inventory'=>1, 'can_saled'=>1, 'topic_count'=>1,'presale_money'=>1,
			);
		}else{
			$some_fields = array(
				'_id'=>1, 'title'=>1, 'created_on'=>1, 'comment_count'=>1,'user_id'=>1,
			);
		}
		
		$query = array();
		$options = array();
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($type){
			$query['type'] = (int)$type;
		}
		$query['event'] = Sher_Core_Model_Favorite::EVENT_FAVORITE;
		
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
			if($type == 1){
				foreach($some_fields as $key=>$value){
					$data[$i][$key] = $result['rows'][$i]['product'][$key];
				}
				$product = new Sher_Core_Model_Product();
				// 获取商品价格区间
				$data[$i]['range_price'] = $product->range_price($result['rows'][$i]['product']['_id'], $result['rows'][$i]['product']['stage']);
				
				// 封面图url
				$data[$i]['cover_url'] = $result['rows'][$i]['product']['cover']['thumbnails']['medium']['view_url'];
			}
			if($type == 2){
				foreach($some_fields as $key=>$value){
					$data[$i][$key] = $result['rows'][$i]['topic'][$key];
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
        return $this->api_json('登录账号和密码不匹配', 3004);
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

		$some_fields = array(

		);
			
		$query   = array();
		$options = array();
		
		// 查询条件
		$query['user_id'] = $user_id;
		
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
		
		// 重建数据结果
		for($i=0;$i<count($result['rows']);$i++){

		}
    print_r($result['rows'][0]);exit;
		
		return $this->api_json('请求成功', 0, $result);
  
  }
	
}

