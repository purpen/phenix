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
	
	protected $exclude_method_list = array('ajax_fetch_list', 'ajax_fetch_comment_site', 'ajax_fetch_comment_wap');
	
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
		$row = array();
		$row['user_id'] = $this->visitor->id;
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['target_user_id'] = (int)$this->stash['target_user_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		// 验证数据
		if(empty($row['target_id']) || empty($row['content'])){
      $this->stash['is_error'] = true;
			$this->stash['note'] = '获取数据错误,请重新提交';
      return $this->to_taconite_page('ajax/note.html');
		}

    $is_reply = isset($this->stash['is_reply'])?(int)$this->stash['is_reply']:0;
    if(!empty($is_reply)){
      $reply_id = isset($this->stash['reply_id'])?$this->stash['reply_id']:null;
      if(empty($reply_id)){
        return $this->ajax_note('回复ID不存在!', true);
      }
      $row['is_reply'] = $is_reply;
      $row['reply_id'] = $reply_id;
    }
		
		$model = new Sher_Core_Model_Comment();
    try{
		  $ok = $model->apply_and_save($row);
      if($ok){
        $comment_id = $model->id;
        $this->stash['comment'] = &$model->extend_load($comment_id);
      } 
    }catch(Exception $e){
      $this->stash['is_error'] = true;
			$this->stash['note'] = $e->getMessage();
      return $this->to_taconite_page('ajax/note.html');  
    }

		return $this->to_taconite_page('ajax/comment_ok.html');
	}
	
	/**
	 * 用户发表评价
	 */
	public function ajax_evaluate(){
		$row = array();
		
		$row['user_id'] = $this->visitor->id;
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
    $row['sku_id'] = isset($this->stash['sku'])?(int)$this->stash['sku']:0;
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content']) || empty($row['star'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}

		$model = new Sher_Core_Model_Comment();

    $query = array();
    $query['user_id'] = $row['user_id'];
    $query['target_id'] = $row['target_id'];
    $query['sku_id'] = $row['sku_id'];
    $query['type'] = $row['type'];
    $has_one = $model->first($query);

    if(!empty($has_one)){
      return $this->ajax_note('该商品不能重复评价!', true);
    }

		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);
		}
		
		return $this->to_taconite_page('ajax/evaluate_ok.html');
	}

	/**
	 * 点赞
	 */
	public function ajax_laud(){
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
				$model->mock_after_remove($this->visitor->id, $id, $type, Sher_Core_Model_Favorite::EVENT_LOVE);
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
  public function ajax_fetch_comment_site(){
    $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
    $this->stash['target_id'] = !empty($this->stash['target_id'])?$this->stash['target_id']:-1;
		$this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
    $this->stash['current_user_id'] = $current_user_id;
    $this->stash['comment_load_type'] = $comment_load_type = isset($this->stash['comment_load_type'])?(int)$this->stash['comment_load_type']:1;

    if($comment_load_type==1){
      $tmp = 'ajax/comment_more_site.html';
    }elseif($comment_load_type==2){
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

    if($comment_load_type==1){
      $tmp = 'ajax/comment_more_wap.html';
    }elseif($comment_load_type==2){
      $tmp = 'ajax/comment_list_wap.html';
    }
		return $this->to_taconite_page($tmp);
  }
  	
}
