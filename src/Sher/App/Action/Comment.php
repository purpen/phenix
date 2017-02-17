<?php
/**
 * 用户评价
 */
class Sher_App_Action_Comment extends Sher_App_Action_Base {
	
	public $stash = array(
		'id'=>'',
        'rid'=>'',
		'user_id'=>'',
		'target_id'=>'',
        'target_user_id'=>0,
		'page'=>1,
		'next_page'=>1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array('ajax_fetch_list', 'ajax_fetch_comment_site', 'ajax_fetch_comment_wap', 'ajax_fetch_comment');
	
	/**
	 * 
	 */
	public function execute(){		
		return $this->get_list();
	}
	
	/**
	 * 评价列表
	 */
	public function get_list(){}
	
	/**
	 * ajax获取评论列表
	 */
	public function ajax_fetch_list(){
		$page = (int)$this->stash['page'];
		$this->stash['next_page'] += $page;
        $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
        $this->stash['current_user_id'] = $current_user_id;

		return $this->to_taconite_page('ajax/comment_list.html');
	}
	
	/**
	 * 保存评论
	 */
	public function do_save(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			return $this->ajax_json('您不能参与评论 !', true);
		}

    $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : null;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(empty($target_id)){
			return $this->ajax_json('缺少请求参数 !', true);
		}

    // 用户发表频率、次数限制
    if(empty($this->visitor->quality)){
      $pub_is_limit = Sher_Core_Helper_Util::report_filter_limit($this->visitor->id, 5, array('target_id'=>$target_id, 'type'=>$type));
      if($pub_is_limit['success']){
        return $this->ajax_json($pub_is_limit['msg'], true);   
      }     
    }
        
		$from_to = isset($this->stash['from_to'])?$this->stash['from_to']:'web';
		$row = array();
		$row['user_id'] = $this->visitor->id;
		$row['star'] = isset($this->stash['star']) ? (int)$this->stash['star'] : 0;
		$row['target_id'] = $target_id;
		$row['target_user_id'] = (int)$this->stash['target_user_id'];
		$row['type'] = $type;
		$row['sku_id'] = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;
		$row['from_site'] = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 1;
		
		// 处理评论内容
		$content = $this->stash['content'];
		$user = new Sher_Core_Model_User();
		$remind = new Sher_Core_Model_Remind();
		$url = Doggy_Config::$vars['app.url.user'];

		if(empty($row['target_id'])){
            return $this->ajax_json('缺少请求参数 !', true);
        }
		if(empty($content)){
            return $this->ajax_json('评论内容不能为空!', true);
        }
        $user_ids = array();
        $merge = '/\@(.*) /U';
        $content = preg_replace_callback($merge,
            function($s) use($user,$url,$remind,&$user_ids){
				if(!empty($s[1]) && strlen($s[1]) <= 25){
					$userInfo = $user->first(array('nickname'=>(string)$s[1]));
					if($userInfo){
						$img = '[at:'.$url.'/'.$userInfo['_id'].'::@'.$userInfo['nickname'].':]';
            array_push($user_ids, $userInfo['_id']);
					}
				} else {
					$img = '';
				}
				return $img;
            },$content
        );
		$row['content'] = $content;
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content'])){
            $this->stash['is_error'] = true;
			$this->stash['note'] = '获取数据错误,请重新提交';
            return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
        $is_reply = isset($this->stash['is_reply'])?(int)$this->stash['is_reply']:0;
        if(!empty($is_reply)){
            $reply_id = isset($this->stash['reply_id'])?$this->stash['reply_id']:null;
            $reply_user_id = isset($this->stash['reply_user_id'])?(int)$this->stash['reply_user_id']:0;
            if(empty($reply_id)){
            return $this->ajax_json('回复ID不存在!', true);
            }
            if(empty($reply_user_id)){
            return $this->ajax_json('回复用户ID不存在!', true);
            }
            $row['is_reply'] = $is_reply;
            $row['reply_id'] = $reply_id;
            $row['reply_user_id'] = $reply_user_id;
        }
		
        try{
		        $model = new Sher_Core_Model_Comment();

            // 如果是神嘴争霸，验证用户是否首次评论
            $rank_has_first_comment = $this->stash['rank_has_first_comment'] = false;
            if($row['type']==Sher_Core_Model_Comment::TYPE_SUBJECT && (int)$row['target_id']==5){
              $has_comment = $model->count(array('type'=>$row['type'], 'target_id'=>$row['target_id'], 'user_id'=>$row['user_id']));
              if($has_comment==0){
                $rank_has_first_comment = $this->stash['rank_has_first_comment'] = true;
              }
            
            }

            $ok = $model->apply_and_save($row);
            if($ok){
                $comment_id = $model->id;
                
				//echo $comment_id;
                $comment = $this->stash['comment'] = &$model->extend_load($comment_id);

          if(!empty($user_ids)){
            $has_send_users = array();
            foreach($user_ids as $uid){
              $uid = (int)$uid;
              // 如果已经发送过，跳过
              if(in_array($uid, $has_send_users)) continue;
							// 给用户添加提醒
							$arr = array(
								'user_id'=> $uid,
								's_user_id'=> (int)$this->visitor->id,
								'evt'=> Sher_Core_Model_Remind::EVT_AT,
								'kind'=> Sher_Core_Model_Remind::KIND_COMMENT,
								'related_id'=> (string)$comment_id,
								'parent_related_id'=> (int)$this->stash['target_id'],
							);
							$ok = $remind->create($arr);
              array_push($has_send_users, $uid);
            
            }
          
          }
            } 
        }catch(Sher_Core_Model_Exception $e){
            $this->stash['is_error'] = true;
			$this->stash['note'] = $e->getMessage();
            return $this->ajax_json($e->getMessage(), true);  
        }

        //加载赞
        $favorite = new Sher_Core_Model_Favorite();

        $is_deleted = !empty($comment['deleted']) ? true : false;
        $is_reply = !empty($comment['is_reply']) ? true : false;
        $is_love_count = !empty($comment['love_count']) ? true : false;
        // 是否有删除权限
        if($this->visitor->can_admin || (int)$this->visitor->id==$comment['user_id']){
          $comment['is_edit'] = true;           
        }else{
          $comment['is_edit'] = false;          
        }

        $is_old_reply = !empty($comment['reply']) ? true : false;

        $is_loved = $favorite->check_loved((int)$this->visitor->id, (string)$comment['_id'], Sher_Core_Model_Favorite::TYPE_COMMENT);

        // 加载评分
        if(!empty($is_star)){
          $star = isset($comment['star']) ? (int)$comment['star'] : 0;
          switch($star){
            case 0:
              $comment['star0'] = true;
              break;
            case 1:
              $comment['star1'] = true;
              break;
            case 2:
              $comment['star2'] = true;
              break;
            case 3:
              $comment['star3'] = true;
              break;
            case 4:
              $comment['star4'] = true;
              break;
            case 5:
              $comment['star5'] = true;
              break;
          }
        }

        // 过滤用户表
        if(isset($comment['user'])){
          $comment['user'] = Sher_Core_Helper_FilterFields::user_list($comment['user']);
        }
        // 过滤用户表
        if(isset($comment['target_user'])){
          $comment['target_user'] = Sher_Core_Helper_FilterFields::user_list($comment['target_user']);
        }

        $comment['_id'] = (string)$comment['_id'];
        $comment['is_deleted'] = $is_deleted;
        $comment['is_reply'] = $is_reply;
        $comment['is_love_count'] = $is_love_count;
        $comment['is_old_reply'] = $is_old_reply;
        $comment['is_loved'] = $is_loved;

        $data = array();

        if($this->visitor->id){
          $comment['is_login'] = true;
          if($this->visitor->can_admin){
            $comment['can_admin'] = true;
          }
        }else{
          $comment['is_login'] = false;
        }

        $comment['from_site'] = $from_to;

        // 神嘴争霸wap 以后去掉
        $comment['rank_has_first_comment'] = $rank_has_first_comment;

        // 查看该贴子是否属于评论分享贴
        $is_comment_share = $this->is_comment_share($comment['target_id'], $comment['type']);
        $comment['is_comment_share'] = $is_comment_share;

    return $this->ajax_json('操作成功', false, '', $comment);

	}
	
	/**
	 * 用户发表评价
	 */
	public function ajax_evaluate(){

		$order_id = isset($this->stash['order_id'])?$this->stash['order_id']:null;
		
		$row = array();
		$row['user_id'] = $this->visitor->id;
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		$row['sku_id'] = isset($this->stash['sku'])?(int)$this->stash['sku']:0;
		$row['from_site'] = isset($this->stash['from_site'])?(int)$this->stash['from_site']:1;
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content']) || empty($row['star'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}

		$model = new Sher_Core_Model_Comment();

		$query = array();
		$query['target_id'] = $row['target_id'];
		$query['type'] = $row['type'];
		$query['user_id'] = $row['user_id'];
		$query['sku_id'] = $row['sku_id'];
    // 不验证重复性
    //$has_one = $model->first($query);
    $has_one = false;
		if(!empty($has_one)){
		  return $this->ajax_note('该商品不能重复评价!', true);
		}

		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);

      // 更新订单状态为完成
      if(!empty($order_id)){
        $orders_model = new Sher_Core_Model_Orders();
        $order = $orders_model->load((string)$order_id);
        $rid = $order['rid'];
        if(!empty($order) && $order['user_id']==$this->visitor->id && $order['status']==Sher_Core_Util_Constant::ORDER_EVALUATE){
            
            // 检测是否含有推广记录,更新佣金结算状态
            $is_referral = $is_storage = false;
            if(!empty($order['referral_code'])) $is_referral = true;
            for($i=0;$i<count($order['items']);$i++){
                $item = $order['items'][$i];
                $referral_code = isset($item['referral_code']) ? $item['referral_code'] : null;
                $storage_id = isset($item['storage_id']) ? $item['storage_id'] : null;
                if(!empty($storage_id)){
                    $is_storage = true;
                    break;
                }
            }// endfor

          $order_ok = $orders_model->finish_order($order_id, array('user_id'=>$order['user_id'], 'rid'=>$rid, 'is_referral'=>$is_referral, 'is_storage'=>$is_storage));
        }
      }
		} // if ok
		
		return $this->to_taconite_page('ajax/evaluate_ok.html');
	}

	/**
	 * 用户发表评价--批量
	 */
	public function batch_ajax_evaluate(){

		$order_id = isset($this->stash['order_id'])?$this->stash['order_id']:null;
        $type = isset($this->stash['type'])?(int)$this->stash['type']:0;
        $from_site = isset($this->stash['from_site'])?(int)$this->stash['from_site']:1;

        $target_id = isset($this->stash['target_id'])?(array)$this->stash['target_id']:array();
        $sku = isset($this->stash['sku'])?(array)$this->stash['sku']:array();
        $star = isset($this->stash['star'])?(array)$this->stash['star']:array();
        $content = isset($this->stash['content'])?(array)$this->stash['content']:array();

        if(empty($order_id) || empty($type)){
            return $this->ajax_json('缺少请求参数!', true);
        }

        $orders_model = new Sher_Core_Model_Orders();
        $order = $orders_model->load((string)$order_id);

        if(empty($order)){
            return $this->ajax_json('订单不存在!', true);
        }

        if($order['user_id'] != $this->visitor->id){
            return $this->ajax_json('没有权限!', true);
        }

        if($order['status'] != Sher_Core_Util_Constant::ORDER_EVALUATE){
            return $this->ajax_json('订单类型不正确!', true);       
        }

        $target_arr = array();
        $sku_arr = array();
        $star_arr = array();
        $content_arr = array();
        $rid = $order['rid'];

        // 组装、验证数据
        for($i=0;$i<count($target_id);$i++){
            if(empty($content[$i])){
                return $this->ajax_json('请添写内容!', true);
            }
            if(empty($star[$i])){
                return $this->ajax_json('请打分数!', true);
            }
            array_push($target_arr, $target_id[$i]);
            array_push($sku_arr, (int)$sku[$i]);
            array_push($star_arr, (int)$star[$i]);
            array_push($content_arr, $content[$i]);
        }

        if(empty($target_arr)){
            return $this->ajax_json('产品不存在!', true);           
        }

        $model = new Sher_Core_Model_Comment();

        for($i=0;$i<count($target_arr);$i++){
            $row = array();
            $row['user_id'] = $this->visitor->id;
            $row['star'] = $star_arr[$i];
            $row['target_id'] = $target_arr[$i];
            $row['content'] = $content_arr[$i];
            $row['type'] = $type;
            $row['sku_id'] = $sku_arr[$i];
            $row['from_site'] = $from_site;

            try{
                $ok = $model->create($row);

                if(!$ok){
                    return $this->ajax_json('保存失败!', true);
                }            
            }catch(Sher_Core_Model_Exception $e){
                return $this->ajax_json($e->getMessage(), true);           
            }
        
        }

        // 检测是否含有推广记录,更新佣金结算状态
        $is_referral = $is_storage = false;
        if(!empty($order['referral_code'])) $is_referral = true;
        for($i=0;$i<count($order['items']);$i++){
            $item = $order['items'][$i];
            $referral_code = isset($item['referral_code']) ? $item['referral_code'] : null;
            $storage_id = isset($item['storage_id']) ? $item['storage_id'] : null;
            if(!empty($storage_id)){
                $is_storage = true;
                break;
            }
        }// endfor

        // 更新订单状态为完成
        $order_ok = $orders_model->finish_order($order_id, array('user_id'=>$order['user_id'], 'rid'=>$rid, 'is_referral'=>$is_referral, 'is_storage'=>$is_storage));
		
        return $this->ajax_json('操作成功!', false);
    }

	/**
	 * 点赞
	 */
	public function ajax_laud(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			return $this->ajax_json('您不能参与点赞 !', true);
		}
		
		$id = $this->stash['id'];
		$type = Sher_Core_Model_Favorite::TYPE_COMMENT;
		if(empty($id)){
			return $this->ajax_note('缺少请求参数！', true);
		}

		$this->stash['mode'] = 'create';
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => $type,
			);
			if (!$model->check_loved($this->visitor->id, (string)$id, $type)) {
				$ok = $model->add_love($this->visitor->id, (string)$id, $fav_info);
                if($ok){
                    // 获取计数
                    $model = new Sher_Core_Model_Comment();
                    $comment = $model->find_by_id($id);
                    $this->stash['love_count'] = $comment['love_count'];
                    return $this->to_taconite_page('ajax/laud_ok.html');
                }else{
                    return $this->ajax_note('添加失败！', true);
                }
            }else{
                return $this->ajax_note('已添加喜欢', true);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('操作失败,请重新再试:'.$e->getMessage(), true);
		}
		
	}
	
	/**
	 * 取消点赞
	 */
	public function ajax_cancel_laud(){
		$id = $this->stash['id'];
		$type = Sher_Core_Model_Favorite::TYPE_COMMENT;
		if(empty($id) || empty($type)){
			return $this->ajax_note('缺少请求参数！', true);
		}

		$this->stash['mode'] = 'cancel';
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->cancel_love($this->visitor->id, $id, $type);
			if($ok){
        // 获取计数
        $model = new Sher_Core_Model_Comment();
        $comment = $model->find_by_id($id);
        $this->stash['love_count'] = $comment['love_count'];
        return $this->to_taconite_page('ajax/laud_ok.html');
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('操作失败,请重新再试', true);
		}
		
	}
	
	/**
	 * 删除回应 非物理删除,加上楼层,改为屏蔽
	 */
	public function delete(){
		$comment_id = $this->stash['id'];
		// 验证数据
		if(empty($comment_id)){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}
		
		try{
			$model = new Sher_Core_Model_Comment();
			$comment = $model->find_by_id($comment_id);
			// 非管理员只能删除自己的评论
			if ($this->visitor->can_admin() || $comment['user_id'] == $this->visitor->id){
        $ok = $model->mark_remove($comment_id);
        if($ok){
          // 更新对应对象的回应数 --注掉,因为是屏蔽评论,相关数量不做减少
          //$model->mock_after_remove($comment);       
        }else{
          return $this->ajax_note('删除失败!');
        }
				
			}
			
			$this->stash['ids'] = array($comment_id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('删除评论失败,请重新提交', true);
		}
		
		return $this->ajax_delete('删除成功', false);
	}

  /**
   * 删除评论回复
   */
  public function del_reply(){
    $comment_id = $this->stash['id'];
    $rid = $this->stash['rid'];
		// 验证数据
		if(empty($comment_id) || empty($this->stash['rid'])){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}

		try{
			$model = new Sher_Core_Model_Comment();
			$comment = $model->find_by_id($comment_id);
			// 非管理员只能删除自己的评论
			//if ($this->visitor->can_admin() || $comment['reply']['r_id']['user_id'] == $this->visitor->id){
				$model->remove_reply($comment_id, $rid);
			//}
      //如果是管理员
      if($this->visitor->can_admin()){
      	$model->remove_reply($comment_id, $rid);
      }else{
        $reply_user_id = 0;
        foreach($comment['reply'] as $key=>$val){
          if((string)$val['r_id']==$rid){
            $reply_user_id = (int)$val['user_id'];
            break;
          }
        }
        //如果是用户本人
        if(!empty($reply_user_id) && $this->visitor->id==$reply_user_id){
          $model->remove_reply($comment_id, $rid);  
        }
      }
			
			$this->stash['ids'] = array($rid);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('删除回复失败,请重新提交', true);
		}
		
		return $this->ajax_delete('删除成功', false);

  }
	
	/**
	 * 保存回复
	 */
	public function ajax_reply(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			return $this->ajax_json('您不能参与点赞 !', true);
		}
		
		$comment_id = $this->stash['comment_id'];
		$target_id = $this->stash['target_id'];
		
		$user_id = $this->visitor->id;
		$content = $this->stash['content'];
		
		// 验证数据
		if(empty($comment_id) || empty($content)){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$result = $model->create_reply($comment_id, $user_id, $content);
		
		if(!empty($result)){
			$result['user'] = & DoggyX_Model_Mapper::load_model($user_id, 'Sher_Core_Model_User');
			$result['replied_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($result['replied_on']);
		}
		
		$this->stash['reply'] = $result;
		
		return $this->to_taconite_page('ajax/reply_ok.html');
	}
	
	/**
	 * 删除回复
	 */
	public function delete_reply(){
		$comment_id = $this->stash['id'];
		$reply_id = $this->stash['r_id'];
		// 验证数据
		if(empty($comment_id) || empty($reply_id)){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}
		
		try{
			$model = new Sher_Core_Model_Comment();
			$ok = $model->remove_reply($comment_id, $reply_id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('删除回复失败,请重新提交', true);
		}
		
		$this->stash['ids'] = array($reply_id);
		
		return $this->ajax_delete('删除成功', false);
	}

    /**
     * ajax获取评论--site
     */
    public function ajax_fetch_comment_site() {
        $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
        $this->stash['target_id'] = !empty($this->stash['target_id'])?$this->stash['target_id']:-1;
        $this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
        $this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
        $this->stash['current_user_id'] = $current_user_id;
        $this->stash['comment_load_type'] = $comment_load_type = isset($this->stash['comment_load_type'])?(int)$this->stash['comment_load_type']:1;
        $this->stash['sort'] = isset($this->stash['sort'])?(int)$this->stash['sort']:0;

        if($comment_load_type == 1){
            $tmp = 'ajax/comment_more_site.html';
        }elseif($comment_load_type == 2){
            $tmp = 'ajax/comment_list_site.html';
        }

        return $this->to_taconite_page($tmp);
    }
    
    /**
     * ajax获取评论--wap
     */
    public function ajax_fetch_comment_wap(){
        $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
        $this->stash['target_id'] = !empty($this->stash['target_id'])?$this->stash['target_id']:-1;
        $this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
        $this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
        $this->stash['current_user_id'] = $current_user_id;
        $this->stash['comment_load_type'] = $comment_load_type = isset($this->stash['comment_load_type'])?(int)$this->stash['comment_load_type']:1;
        $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

        if($comment_load_type==1){
          $tmp = 'ajax/comment_more_wap.html';
        }elseif($comment_load_type==2){
          $tmp = 'ajax/comment_list_wap.html';
        }

        // 查看该贴子是否属于评论分享贴
        $is_comment_share = $this->is_comment_share($this->stash['target_id'], (int)$this->stash['type']);
        $this->stash['is_comment_share'] = $is_comment_share;

        return $this->to_taconite_page($tmp);
    }

    /**
     * ajax获取评论(new mustache)
     */
    public function ajax_fetch_comment() {
        $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
        $target_id = !empty($this->stash['target_id'])?$this->stash['target_id']:0;
        $page = isset($this->stash['page'])?(int)$this->stash['page']:1;
        $per_page = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
        $type = isset($this->stash['type'])?(int)$this->stash['type']:0;
        // 加载类型为 分页或更多, 默认更多
        $comment_load_type = isset($this->stash['comment_load_type'])?(int)$this->stash['comment_load_type']:1;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        // wap 或 site
        $from_site = isset($this->stash['from_site']) ? $this->stash['from_site'] : 'site';
        // 是否加载评分
        $is_star = isset($this->stash['is_star']) ? (int)$this->stash['is_star'] : 0;
        // 子分类
        $sub_type = isset($this->stash['sub_type']) ? (int)$this->stash['sub_type'] : 0;

        $next_id = isset($this->stash['next_id']) ? DoggyX_Mongo_Db::id($this->stash['next_id']) : null;
        
        $service = Sher_Core_Service_Comment::instance();
        
        $query = array();
        $options = array();

        if($next_id){
          $query['_id'] = array('$gt'=>$next_id);
        }

        if($target_id){
          $query['target_id'] = $target_id;
        }

        if($type){
          $query['type'] = $type;
        }

        if($sub_type){
          $query['sub_type'] = $sub_type;
        }

        $options['page'] = $page;
        $options['size'] = $per_page;

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
        
        $resultlist = $service->get_comment_list($query,$options);
        
        $next_page = 'no';
        if(isset($resultlist['next_page'])){
            if((int)$resultlist['next_page'] > $page){
                $next_page = (int)$resultlist['next_page'];
            }
        }
                
        // 生成分页
        switch ($type){
            case 2:
                $pager_url = Sher_Core_Helper_Url::topic_view_url($target_id, '#p#');
                break;
            case 3:
                $pager_url = sprintf(Doggy_Config::$vars['app.url.try.comment'], $target_id, '#p#');
                break;
            case 4:
                $pager_url = Sher_Core_Helper_Url::shop_view_url($target_id, '#p#');
                break;
            case 6:
                $pager_url = Sher_Core_Helper_Url::stuff_view_url($target_id, '#p#');
                break;
            default:
                $pager_url = '';
                break;
        }
        // 添加排序
        if ($sort) {
            $pager_url .= '?sort='.$sort;
        } 
        $pager = Sher_Core_Helper_Util::pager($resultlist['total_rows'],$resultlist['total_page'],$page,9,$pager_url);
        
        //加载赞
        $favorite = new Sher_Core_Model_Favorite();
        for($i=0;$i<count($resultlist['rows']);$i++){
          $is_deleted = !empty($resultlist['rows'][$i]['deleted']) ? true : false;
          $is_reply = !empty($resultlist['rows'][$i]['is_reply']) ? true : false;
          $is_love_count = !empty($resultlist['rows'][$i]['love_count']) ? true : false;
          // 是否有删除权限
          if($this->visitor->can_admin || $current_user_id==$resultlist['rows'][$i]['user_id']){
            $resultlist['rows'][$i]['is_edit'] = true;           
          }else{
            $resultlist['rows'][$i]['is_edit'] = false;          
          }

          // 判断回复的内容是否已删除
          if($is_reply && isset($resultlist['rows'][$i]['reply_comment'])){
            $resultlist['rows'][$i]['reply_comment']['is_deleted'] = !empty($resultlist['rows'][$i]['reply_comment']['deleted']) ? true : false;
          }

          $is_old_reply = !empty($result['rows'][$i]['reply']) ? true : false;

          $is_loved = $favorite->check_loved((int)$current_user_id, (string)$resultlist['rows'][$i]['_id'], Sher_Core_Model_Favorite::TYPE_COMMENT);

          // 加载评分
          if(!empty($is_star)){
            $star = isset($resultlist['rows'][$i]['star']) ? (int)$resultlist['rows'][$i]['star'] : 0;
            switch($star){
              case 0:
                $resultlist['rows'][$i]['star0'] = true;
                break;
              case 1:
                $resultlist['rows'][$i]['star1'] = true;
                break;
              case 2:
                $resultlist['rows'][$i]['star2'] = true;
                break;
              case 3:
                $resultlist['rows'][$i]['star3'] = true;
                break;
              case 4:
                $resultlist['rows'][$i]['star4'] = true;
                break;
              case 5:
                $resultlist['rows'][$i]['star5'] = true;
                break;
            }
          }

          // 过滤用户表
          if(isset($resultlist['rows'][$i]['user'])){
            $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user']);
          }
          // 过滤用户表
          if(isset($resultlist['rows'][$i]['target_user'])){
            $resultlist['rows'][$i]['target_user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['target_user']);
          }

          $resultlist['rows'][$i]['_id'] = (string)$resultlist['rows'][$i]['_id'];
          $resultlist['rows'][$i]['is_deleted'] = $is_deleted;
          $resultlist['rows'][$i]['is_reply'] = $is_reply;
          $resultlist['rows'][$i]['is_love_count'] = $is_love_count;
          $resultlist['rows'][$i]['is_old_reply'] = $is_old_reply;
          $resultlist['rows'][$i]['is_loved'] = $is_loved;
        }

        $data = array();

        if($this->visitor->id){
          $data['is_login'] = true;
          if($this->visitor->can_admin){
            $data['can_admin'] = true;
          }
        }else{
          $data['is_login'] = false;
        }

        $data['next_page'] = $next_page;
        $data['page'] = $page;
        $data['from_site'] = $from_site;
        $data['comment_load_type'] = $comment_load_type;
        $data['sort'] = $sort;
        $data['per_page'] = $per_page;
        $data['result'] = $resultlist;
        
        $data['pager'] = $pager;
        
        return $this->ajax_json('', false, '', $data);
    }


    /**
     * 个人中心快捷回复
     */
    public function quick_reply(){
    
    
    }

  /**
   * 判断是否是评论分享的贴子
   */
  protected function is_comment_share($target_id, $type){
    // 查看该贴子是否属于评论分享贴
    $is_comment_share = false;
    if($type==Sher_Core_Model_Comment::TYPE_TOPIC){
      $target_ids = Doggy_Config::$vars['app.topic_comment_ids'];
    }else{
      $target_ids = array();
    }
    if(!empty($target_ids)){
      $target_arr = explode('|', $target_ids);
      for($i=0;$i<count($target_arr);$i++){
        if((int)$target_arr[$i]==(int)$target_id){
          $is_comment_share = true;
          break;
        }
      }
    }
    return $is_comment_share;
  }

	/**
	 * 专题快捷评论
	 */
	public function ajax_subject_save(){

		$user_id = $this->visitor->id;
		$target_id = $this->stash['target_id'];
		$content = $this->stash['content'];
		$type = (int)$this->stash['type'];
		
		// 验证数据
		if(empty($target_id) || empty($content)){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}

		$model = new Sher_Core_Model_Comment();

		$row = array();
		$row['target_id'] = $target_id;
		$row['type'] = $type;
		$row['user_id'] = $user_id;
    $row['content'] = $content;

		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
      return $this->ajax_json('评论成功!', false);
    }else{
		  return $this->ajax_json("评论失败!", true);
    } // if ok
		
	}
  	
}
