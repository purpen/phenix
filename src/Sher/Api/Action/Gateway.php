<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Gateway extends Sher_Api_Action_Base {
	public $stash = array(
		'page' => 1,
		'size' => 10,
		'uid' => 0,
		'c' => '',
		's' => '',
		'bonus' => '',
	);

	protected $exclude_method_list = array('execute', 'slide', 'bonus', 'up_bonus', 'game_result', 'feedback');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->to_raw('Hi Taihuoniao!');
	}
	
	/**
	 * 广告位轮换图
	 */
	public function slide(){
		$result = array();
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		// 请求参数
		$space_id = isset($this->stash['space_id']) ? $this->stash['space_id'] : 0;
		$name = isset($this->stash['name']) ? $this->stash['name'] : '';
    $category_name = isset($this->stash['category_name']) ? $this->stash['category_name'] : '';
		if(empty($name) && empty($space_id)){
			return $this->api_json('请求参数不足', 3000);
		}
		
		// 获取某位置的推荐内容
    if(!empty($name) && empty($space_id)){
			$model = new Sher_Core_Model_Space();
      if(!empty($category_name)){
        $c_name = sprintf("%s_%s", $name, $category_name);
			  $row = $model->first(array('name' => $c_name));
        if(!empty($row)){
          $space_id = (int)$row['_id'];
        }else{
          $row = $model->first(array('name' => $name));
          if(!empty($row)){
            $space_id = (int)$row['_id'];
          }else{
            return $this->api_json('请求参数不足', 3002);
          }
        }
      }else{
        $row = $model->first(array('name' => $name));
        if(!empty($row)){
          $space_id = (int)$row['_id'];
        }else{
          return $this->api_json('请求参数不足', 3002);
        }
      }

		}

    // 
		
		$query   = array();
		$options = array();
		
		// 查询条件
		if ($space_id) {
			$query['space_id'] = (int)$space_id;
		}
		
		$query['state'] = Sher_Core_Model_Advertise::STATE_PUBLISHED;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
        $service = Sher_Core_Service_Advertise::instance();
        $result = $service->get_ad_list($query,$options);
	

    //显示的字段
    $options['some_fields'] = array(
      '_id'=> 1, 'title'=>1, 'space_id'=>1, 'sub_title'=>1, 'web_url'=>1, 'summary'=>1, 'cover_id'=>1, 'type'=>1, 'ordby'=>1, 'kind'=>1,
      'created_on'=>1, 'state'=>1
    );

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
				$data[$i][$key] = $result['rows'][$i][$key];
      }
      $data[$i]['item_id'] = 0;
      $data[$i]['item_stage'] = 0;
      $data[$i]['item_type'] = 'Product';
      //判断是预售还是商品
      //eg: Product-0-1122877465
      if($result['rows'][$i]['type']==2){
        $web_url = $result['rows'][$i]['web_url'];
        if(!empty($web_url)){
          $arr = explode('-', $web_url);
          $data[$i]['item_id'] = $arr[2];
          $data[$i]['item_stage'] = $arr[1];
          $data[$i]['item_type'] = $arr[0];
        }
      }
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['medium']['view_url'];
		}

		$result['rows'] = $data;

		// 获取单条记录 ????
		if($size == 1 && !empty($result['rows'])){
			$result = $result['rows'][0];
		}
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 获取红包
	 */
	public function bonus(){
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop('T9');
		
		# 获取为空，重新生产红包
		while (empty($result)){
			$bonus->create_batch_bonus(10);
			$result = $bonus->pop('T9');
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}
		
		# 返回结果
		$data = array(
			'code' => $result['code'],
			'amount' => $result['amount'],
		);
		
		return $this->ajax_json(200, false, null,$data);
	}
	
	/**
	 * 更新红包
	 */
	public function up_bonus(){
		$code = $this->stash['c'];
		$state = $this->stash['s']; // <0:未中,1:击中>
		if (empty($code) || !isset($state)) {
			return $this->ajax_json('缺少更新参数', true);
		}
		
		// 查看是否存在
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->find_by_code($code);
		
		if (empty($result)) {
			return $this->ajax_json('此红包不存在或已被删除！', true);
		}
		
		$id = (string)$result['_id'];
		$state = (int)$state;
		
		// 击中红包开始锁定
		if ($state == 1) {
			$bonus->locked($id);
		} else if ($state == 0) {
			// 未击中红包，进行释放
			if ($result['status'] == Sher_Core_Model_Bonus::STATUS_PENDING){
				$bonus->unpending($id);
			}
		} else {
			return $this->ajax_json('未知红包状态！', true);
		}
		
		return $this->ajax_json('操作成功！');
	}
	
	/**
	 * 领取红包
	 */
	public function got_bonus(){
		$bonus = $this->stash['bonus'];
		$user_id = $this->stash['uid'];
		if (empty($bonus) || empty($user_id)){
			return $this->ajax_json('领取失败：缺少请求参数！', true);
		}
		
		$bonus_list = preg_split('/[;]+/u', $bonus);
		if(empty($bonus_list)){
			return $this->ajax_json('领取失败：未获取红包信息！', true);
		}
		
		for($i=0; $i<count($bonus_list); $i++){
			$code = $bonus_list[$i];
			
			// 查看是否存在
			$model = new Sher_Core_Model_Bonus();
			$result = $model->find_by_code($code);
			
			if (empty($result)){
				Doggy_Log_Helper::warn('领取失败：红包不存在或已被删除！');
				continue;
			}
			
			// 是否使用过
			if ($result['used'] == Sher_Core_Model_Bonus::USED_OK){
				Doggy_Log_Helper::warn('领取失败：红包已被使用！');
				continue;
			}
			
			// 是否过期
			if ($result['expired_at'] && $result['expired_at'] < time()){
				Doggy_Log_Helper::warn('领取失败：红包已被过期！');
				continue;
			}
		
			// 是否失效
			if ($result['status'] == Sher_Core_Model_Bonus::STATUS_DISABLED){
				Doggy_Log_Helper::warn('领取失败：红包已失效不能使用！');
				continue;
			}
			
			$ok = $model->give_user($code, $user_id);
		}
		
		return $this->ajax_json('领取成功！');
	}
	
	/**
	 * 更新游戏结果
	 */
	public function game_result(){
		$uid = $this->stash['uid'];
		$result = $this->stash['bonus'];
		if(empty($result)){
			return $this->ajax_json('提交失败：缺少更新参数', true);
		}
		
		$bonus_list = preg_split('/[;]+/u', $result);
		if(empty($bonus_list)){
			return $this->ajax_json('提交失败：未获取红包信息', true);
		}
		
		for($i=0; $i<count($bonus_list); $i++){
			$bonus = preg_split('/[:]+/u', $bonus_list[$i]);
			$code = $bonus[0];
			$state = (int)$bonus[1];
			
			if($state != 0 && $state != 1){
				Doggy_Log_Helper::warn("Bonus state [$state] error!");
				continue;
			}
			
			// 查看是否存在
			$model = new Sher_Core_Model_Bonus();
			$row = $model->find_by_code($code);
			if(empty($row)){
				Doggy_Log_Helper::warn("Bonus [$code] not exist!");
				continue;
			}
		
			$id = (string)$row['_id'];
			// 击中红包开始锁定
			if($state == 1){
				$ok = $model->locked($id);
			}else{
				// 未击中红包，进行释放
				if($row['status'] == Sher_Core_Model_Bonus::STATUS_PENDING){
					$ok = $model->unpending($id);
				}
			}
		}
		
		return $this->ajax_json('提交成功！');
	}
	
	
	/**
	 * 意见反馈
	 */
	public function feedback(){
		$user_id = $this->current_user_id;
		$content = $this->stash['content'];
		$contact = $this->stash['contact'];
		
		if(empty($user_id) || empty($content) || empty($contact)){
			return $this->api_json('请求参数不足', 3000);
		}
		
		try{
			$model = new Sher_Core_Model_Feedback();
			
			$ok = $model->create(array(
				'user_id' => $user_id,
				'content' => $content,
				'contact' => $contact,
			));
			
			if(!$ok){
				return $this->api_json('提交失败，请重试！', 4002);
			}
		}catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to update feedback:'.$e->getMessage());
            return $this->api_json("更新失败:".$e->getMessage(), 4001);
		}
		
		return $this->api_json('意见反馈成功！', 0);
	}

	
}

