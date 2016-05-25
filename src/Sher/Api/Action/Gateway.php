<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Gateway extends Sher_Api_Action_Base {

	protected $filter_user_method_list = '*';
	
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
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 6;
		
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

			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['fileurl'];
		}

		$result['rows'] = $data;

		// 获取单条记录 ????
		if($size == 1 && !empty($result['rows'])){
			$result = $result['rows'][0];
		}
		
		return $this->api_json('请求成功', 0, $result);
	}

  /**
   * 记录用户激活状态
   */
  public function record_user_active(){
    // 记录用户激活状态（先放此处，下个版本提出来）
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    $channel_id = isset($this->stash['channel']) ? (int)$this->stash['channel'] : 0;
    $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    $idfa = isset($this->stash['idfa']) ? $this->stash['idfa'] : null;

    if(!empty($uuid)){
      $app_user_record_model = new Sher_Core_Model_AppUserRecord();
      $has_app_one = $app_user_record_model->first(array('uuid'=>$uuid));
      if(empty($has_app_one)){
        $app_user_rows = array(
          'uuid' => $uuid,
          'channel_id' => $channel_id,
          'device' => empty($channel_id) ? 2 : 1,
          'kind' => $app_type==1 ? 1 : 2,
          'idfa' => $idfa,
        );
        $app_user_record_model->apply_and_save($app_user_rows);
      }
    }
    return $this->api_json('success', 0, array('uuid'=>$uuid));
  }

  /**
   * app首页秒杀展示
   */
  public function snatched_index_show(){

    // 记录用户激活状态（先放此处，下个版本提出来）
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    $channel_id = isset($this->stash['channel']) ? (int)$this->stash['channel'] : 0;
    $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    $idfa = isset($this->stash['idfa']) ? $this->stash['idfa'] : null;

    if(!empty($uuid)){
      $app_user_record_model = new Sher_Core_Model_AppUserRecord();
      $has_app_one = $app_user_record_model->first(array('uuid'=>$uuid));
      if(empty($has_app_one)){
        $app_user_rows = array(
          'uuid' => $uuid,
          'channel_id' => $channel_id,
          'device' => empty($channel_id) ? 2 : 1,
          'kind' => $app_type==1 ? 1 : 2,
          'idfa' => $idfa,
        );
        $app_user_record_model->apply_and_save($app_user_rows);
      }
    }

    $conf = Sher_Core_Util_View::load_block('app_snatched_index_conf', 1);
    if(empty($conf)){
		  return $this->api_json('数据不存在!', 3001); 
    }
    $arr = explode('|', $conf);
    if(!empty($arr) && count($arr)==3){
      $product_id = (int)$arr[0];
      $cover_url = $arr[1];
      $type = (int)$arr[2];
		  $product_model = new Sher_Core_Model_Product();
		  $product = $product_model->load($product_id);
      if(empty($product)){
  		  return $this->api_json('产品不存在!', 3003);     
      }
      if(!isset($product['app_snatched']) || empty($product['app_snatched'])){
   		  return $this->api_json('非抢购产品!', 3004);     
      }
      $type = 0;
      $begin_time = $product['app_snatched_time'];
      $end_time = $product['app_snatched_end_time'];
      $now_time = time();
      if($begin_time>$now_time){  // 未开始
        $type = 1;
        $time_lag = $begin_time - $now_time;
      }elseif($end_time>$now_time){ // 结束时间
        $type = 2;
        $time_lag = $end_time - $now_time;
      }else{
        $time_lag = 0;
      }

 		  return $this->api_json('请求成功', 0, array('cover_url'=>$cover_url, 'title'=>$product['title'], 'target_id'=>$product['_id'], 'type'=>$type, 'time_lag'=>$time_lag));     
    }else{
 		  return $this->api_json('数据结构不正确!', 3002);   
    } 
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
		$code = isset($this->stash['c']) ? $this->stash['c'] : null;
		$state = isset($this->stash['s']) ? $this->stash['s'] : null; // <0:未中,1:击中>
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
		$bonus = isset($this->stash['bonus']) ? $this->stash['bonus'] : null;
		$user_id = isset($this->stash['uid']) ? $this->stash['uid'] : 0;
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
		$uid = isset($this->stash['uid']) ? $this->stash['uid'] : 0;
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
		$content = isset($this->stash['content']) ? $this->stash['content'] : null;
		$contact = isset($this->stash['contact']) ? $this->stash['contact'] : null;
		$app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
    if($app_type==1){
      $kind=2;
    }elseif($app_type==2){
      $kind=3;
    }else{
      $kind=2;
    }
		
		if(empty($content)){
			return $this->api_json('请求参数不足', 3000);
		}
		
		try{
			$model = new Sher_Core_Model_Feedback();
			
			$ok = $model->create(array(
				'user_id' => $user_id,
				'content' => $content,
				'contact' => $contact,
        'from_to' => $from_to,
        'kind' => $kind,
			));
			
			if(!$ok){
				return $this->api_json('提交失败，请重试！', 3001);
      }

		  return $this->api_json('意见反馈成功！', 0, array('id'=>''));
		}catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to update feedback:'.$e->getMessage());
            return $this->api_json("更新失败:".$e->getMessage(), 3002);
		}
		
	}

  /**
   * 商品搜索热门关键词
   */
  public function get_hot_search_tags(){
    $tag_arr = array();
    // 从块获取信息
    $tags = Sher_Core_Util_View::load_block('app_search_hot_tags', 1);
    $tag_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$tags)));
    return $this->api_json("获取成功!", 0, array('tags'=>$tag_arr));
  }


  /**
   * 返回二维码数据(test)
   */
  public function fetch_qr_code(){
    $pid = isset($this->stash['pid']) ? $this->stash['pid'] : 1;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $str = isset($this->stash['str']) ? $this->stash['str'] : 'http://m.taihuoniao.com';
    return $this->api_json('success!', 0, array('pid'=>$pid, 'type'=>$type, 'str'=>$str));
  }

	
}

