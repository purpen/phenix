<?php
/**
 * 评论管理Model
 * @author purpen
 */
class Sher_Core_Model_Comment extends Sher_Core_Model_Base  {

    protected $collection = "comment";
    
	protected $auto_update_timestamp = true;
	
    protected $created_timestamp_fields = array('created_on', 'updated_on');
    
    const TYPE_USER = 1;
    const TYPE_TOPIC = 2;
	const TYPE_TRY = 3;
	const TYPE_PRODUCT = 4;
  	const TYPE_ACTIVE = 5;
	const TYPE_STUFF  = 6;
    const TYPE_ALBUM = 7;
    const TYPE_SPECIAL_SUBJECT = 8;
    const TYPE_APP_SUBJECT = 9; // app 专题评论
    // 专题评论 target_id 1:云马C1争霸; 5.奶爸奶妈PK; 2.--; 3.--; 4.--
    const TYPE_SUBJECT = 10;
    const TYPE_GPRODUCT = 11; // 情景商品分类
	
    protected $schema = array(
        'user_id' => 0,
        'target_id' => 0,
        'target_user_id' => 0,
        //指定商品sku, 如果是专题，类型为正方反方
        'sku_id' => 0,
		'star' => 0,
        'content' => '',
        'reply' => array(),
        'type' => self::TYPE_TOPIC,
        // 子类型:
        // Product: 15.灵感; 2.
        // Stuff: 5.top100;
        'sub_type' => 0,
		'love_count' => 0,
        // 虚拟点赞人数
        'invented_love_count' => 0,
        
        // 是否是回复某人的评论
        'is_reply' => 0,
        // 回复ID
        'reply_id' => null,
        // 被回复人ID
        'reply_user_id' => 0,
        // 楼层
        'floor' => 0,
        'deleted' => 0,
        'ip' => null,
        // 来源: 0.无记录; 1.web;2.wap;3.ios;4.android;5.win;6.ipad;6.--
        'from_site' => 0,
    );

    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
        'target_user' => array('target_user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array('user_id','content');
    protected $int_fields = array('user_id','target_user_id','star','love_count','floor','is_reply','reply_user_id','invented_love_count');
	protected $counter_fields = array('love_count','invented_love_count');
	
	/**
	 * 验证数据
	 */
    protected function validate(){
    	// 内容长度介于5到1000字符之间(1个中文算3个字符),商品评价不限制
      if($this->data['type']!=self::TYPE_PRODUCT){
        if(strlen($this->data['content']) < 5 || strlen($this->data['content']) > 3000){
          $this->data['error'] = '内容长度介于5到1000字符之间';
          return false;
        }     
      }

		
		return true;
    }

	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
    // 记录IP
    $ip = Sher_Core_Helper_Auth::get_ip();
    if($ip) $data['ip'] = $ip;

    $type = $data['type'];

    if(empty($data['floor'])){
      $target_model = null;
      switch($type){
        case 2:
          $target_model = new Sher_Core_Model_Topic();
          break;
        case 3:
          $target_model = new Sher_Core_Model_Try();
          break;
        case 4:
          $target_model = new Sher_Core_Model_Product();
          break;
        case 6:
          $target_model = new Sher_Core_Model_Stuff();
          break;
        case 7:
          $target_model = new Sher_Core_Model_Albums();
          break;
        default:
          $target_model = null;
      }

      // 专题评论
      if($data['type']==self::TYPE_SUBJECT){
        $dig_model = new Sher_Core_Model_DigList();
        $dig_key = null;
        switch((int)$data['target_id']){
          case 1:
            $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;
            break;
          case 5:
            $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;
            break;
        }
        if(!empty($dig_key)){
          $dig = $dig_model->load($dig_key);
          if(!empty($dig) && isset($dig['items']) && isset($dig['items']['comment_count'])){
            $data['floor'] = (int)$dig['items']['comment_count'] + 1;
          }else{
            $data['floor'] = 1;
          }
        }
      }else{
        if($target_model){
          $target = $target_model->load((int)$data['target_id']);
          if($target){
            $data['floor'] = $target['comment_count'] + 1;
            // 更新子类型
            switch($type){
              case 4:
                $data['sub_type'] = $target['stage'];
                break;
              case 6:
                $data['sub_type'] = $target['from_to'];
                break;
            }// end switch
          } // endif target
        } // endif target_model
      }// endif subject

    }
	  parent::before_save($data);
  }
	
	/**
	 * 关联事件
	 */
    protected function after_save() {
        $user_id = 0;
        // 导入的数据,直接跳过
        if(isset($this->data['product_idea']) && $this->data['product_idea']==1){
          return;
        }
        $timeline_type = 0;
        // 如果是新的记录
        if($this->insert_mode) {
            $type = $this->data['type'];
            switch($type){
                case self::TYPE_TOPIC:
                    $timeline_type = Sher_Core_Util_Constant::TYPE_TOPIC;
                    $kind = Sher_Core_Model_Remind::KIND_TOPIC;
                    
                    $model = new Sher_Core_Model_Topic();
                    // 获取目标用户ID
                    $topic = $model->find_by_id((int)$this->data['target_id']);
                    $user_id = $topic['user_id'];
                    $model->update_last_reply((int)$this->data['target_id'], $this->data['user_id'], $this->data['created_on']);
                    
                    // 增加积分
                    $service = Sher_Core_Service_Point::instance();
                    // 回复他人的文章
                    $service->send_event('evt_reply', $this->data['user_id']);
                    // 文章被他人回复
                    $service->send_event('evt_by_reply', $user_id);
                    break;
                case self::TYPE_PRODUCT:
                    $timeline_type = Sher_Core_Util_Constant::TYPE_PRODUCT;
                    $kind = Sher_Core_Model_Remind::KIND_PRODUCT;
                    
                    $model = new Sher_Core_Model_Product();
                    // 获取目标用户ID
                    $product = $model->find_by_id((int)$this->data['target_id']);
                    $user_id = $product['user_id'];
                    $model->update_last_reply((int)$this->data['target_id'], $this->data['user_id'], $this->data['star']);

                    $user_comment_model = new Sher_Core_Model_User();
                    $user_comment = $user_comment_model->find_by_id((int)$this->data['user_id']);
                    // 如果是小号,不增加鸟币
                    if($user_comment && isset($user_comment['kind']) && $user_comment['kind'] != 9){
                      // 增加积分
                      $service = Sher_Core_Service_Point::instance();
                      // 好评+评论
                      $service->send_event('evt_buy_good_comment', $this->data['user_id']);
                      // 鸟币 必须是销售的商品或兑换商品
                      if(in_array($product['stage'], array(9, 12))){
                        $service->make_money_in($this->data['user_id'], 5, '好评赠送鸟币');                     
                      }

                    }
                    
                    break;
                case self::TYPE_TRY:
                    $kind = Sher_Core_Model_Remind::KIND_TRY;
                    $model = new Sher_Core_Model_Try();
                    //获取目标用户ID
                    $try = $model->find_by_id((int)$this->data['target_id']);
                    $user_id = $try['user_id'];
                    $model->increase_counter('comment_count', 1, (int)$this->data['target_id']);
                    break;
                case self::TYPE_STUFF:
                    $timeline_type = Sher_Core_Util_Constant::TYPE_STUFF;
                    $kind = Sher_Core_Model_Remind::KIND_STUFF;
                    
                    $model = new Sher_Core_Model_Stuff();
                    //获取目标用户ID
                    $stuff = $model->find_by_id((int)$this->data['target_id']);
                    $user_id = $stuff['user_id'];
                    $model->inc_counter('comment_count', 1, (int)$this->data['target_id']);
                    break;
                case self::TYPE_ALBUM:
                    // 不添加动态
                    //$timeline_type = Sher_Core_Util_Constant::TYPE_ALBUM;
                    //$kind = Sher_Core_Model_Remind::KIND_ALBUM;
                    
                    $model = new Sher_Core_Model_Albums();
                    //获取目标用户ID
                    $album = $model->find_by_id((int)$this->data['target_id']);
                    $user_id = $album['user_id'];
                    $model->inc_counter('comment_count', 1, (int)$this->data['target_id']);
                    break;
                case self::TYPE_SUBJECT:
                    $kind = Sher_Core_Model_Remind::KIND_SUBJECT;
                    $model = new Sher_Core_Model_DigList();
                    $dig_key = null;
                    switch((int)$this->data['target_id']){
                      case 1:
                        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;
                        break;
                      case 5:
                        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;
                        break;
                    }
                    // 增加评论数
                    if($dig_key){
                      $model->inc($dig_key, 'items.comment_count', 1);
                    }
                    break;
                case self::TYPE_APP_SUBJECT:
                    $model = new Sher_Core_Model_SpecialSubject();
                    //获取目标用户ID
                    $model->inc_counter('comment_count', 1, (int)$this->data['target_id']);
                    break;
                default:
                    break;
            }

            //如果是回复某人评论,给他提醒
            if(isset($this->data['is_reply']) && $this->data['is_reply']==1){
              $remind_model = new Sher_Core_Model_Remind();
              $arr = array(
                  'user_id'=> $this->data['reply_user_id'],
                  's_user_id'=> $this->data['user_id'],
                  'evt'=> Sher_Core_Model_Remind::EVT_REPLY_COMMENT,
                  'kind'=> Sher_Core_Model_Remind::KIND_COMMENT,
                  'related_id'=> (string)$this->data['_id'],
                  'parent_related_id'=> $this->data['target_id'],
              );
              $ok = $remind_model->create($arr);            
            }else{
              // 非回复他人,给创建者回复提醒
              if($user_id){
                  $user = new Sher_Core_Model_User();
                  $user->update_counter_byinc($user_id, 'comment_count', 1);          
              }
            }
            
	          // 添加动态提醒
            if(!empty($timeline_type)){
                $timeline = Sher_Core_Service_Timeline::instance();
                $timeline->broad_target_comment($this->data['user_id'], (int)$this->data['target_id'], $timeline_type, array('comment_id'=>(string)$this->data['_id']));
            }
            
        }
    }

    /**
     * 类型说明
     */
    public function type_str($type){
        $type_str = null;
        switch ((int)$type){
            case self::TYPE_TOPIC:
                $type_str = '话题';
                break;
            case self::TYPE_PRODUCT:
                $type_str = '创意产品';
                break;
            case self::TYPE_STUFF:
                $type_str = '创意灵感';
                break;
        }
        
        return $type_str;
    }
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($data) {
		$target_id = $data['target_id'];
		$type = $data['type'];
		
		switch($type){
			case self::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			case self::TYPE_TRY:
				$model = new Sher_Core_Model_Try();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			case self::TYPE_ACTIVE:
				$model = new Sher_Core_Model_Active();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			case self::TYPE_STUFF:
				$model = new Sher_Core_Model_Stuff();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			case self::TYPE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			default:
				break;
		}
	}
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        
		if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            $row['reply'] = array();
            $row['ori_content'] = htmlspecialchars($row['content']);
            $row['content'] = '因该用户已经被屏蔽,评论被屏蔽';
            return;
    }

    $row['content_original'] = Sher_Core_Util_View::safe($row['content']);
    $row['content'] = $this->trans_content(Sher_Core_Util_View::safe($row['content']));
    $row['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
    if (!empty($row['reply'])) {
        for ($i=0; $i < count($row['reply']); $i++) {
            $this->_extend_comment_reply($row['reply'][$i]);
        }
    }
        
    // 加载回复对象
    if(isset($row['is_reply']) && !empty($row['is_reply'])){
      $reply_comment_obj = $this->extend_load($row['reply_id']);
      $row['reply_comment'] = $reply_comment_obj;
    }

    // 来源
    if(isset($row['from_site'])){
      switch($row['from_site']){
        case 1:
          $row['from'] = 'Web';
          break;
        case 2:
          $row['from'] = 'Wap';
          break;
        case 3:
          $row['from'] = 'IOS';
          break;
        case 4:
          $row['from'] = 'Android';
          break;
        case 5:
          $row['from'] = 'WinPhone';
          break;
        case 6:
          $row['from'] = 'IPad';
          break;
        default:
          $row['from'] = '--';
      }
    }else{
      $row['from'] = '--';
    }

  }
	
	/**
	 * 扩展回复数据
	 */
    public function _extend_comment_reply(&$row) {
        $row['user'] = & DoggyX_Model_Mapper::load_model($row['user_id'],'Sher_Core_Model_User');
        $row['replied_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['replied_on']);
        if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            $row['ori_content'] = htmlspecialchars_decode($row['content']);
            $row['content'] = '因该用户已经被屏蔽,评论被屏蔽';
            $row['reply'] = array();
            return;
        }
    }
	
    /**
     * 创建回复
     */
    public function create_reply($comment_id, $user_id, $content){
    	$reply_row['user_id'] = (int) $user_id;
	    $reply_row['content'] = $content;
	    $reply_row['replied_on'] = time();
	    $reply_row['love_count'] = 0;
	    $reply_row['r_id'] = new MongoId;
	    $updated_row['$push']['reply'] = $reply_row;
		
	   	if ($this->update($comment_id, $updated_row)){
	        $comment_user = $this->extend_load($comment_id);
	        
			// 给用户添加提醒
			$remind = new Sher_Core_Model_Remind();
			$arr = array(
				'user_id'=> $this->data['reply_user_id'],
				's_user_id'=> $this->data['user_id'],
				'evt'=> Sher_Core_Model_Remind::EVT_REPLY_COMMENT,
				'kind'=> Sher_Core_Model_Remind::KIND_COMMENT,
				'related_id'=> (string)$comment_user['_id'],
				'parent_related_id'=> (string)$comment_user['target_id'],
			);
			$ok = $remind->create($arr);
			
			return $reply_row;
		}
	   	return null;
    }

	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$comment = $this->find_by_id((string)$id);
			if(!isset($comment[$field_name]) || $comment[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}

	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}

    /**
     * 删除某评论的回复
     */
    public function remove_reply($comment_id, $reply_id) {
        $removed_reply['r_id'] = new MongoId($reply_id);
        $update_obj['$pull'] = array('reply' => $removed_reply);
        $update_obj['$set'] = array('updated_on' => $removed_reply);
        $criteria = $this->_build_query($comment_id);
		
        return self::$_db->pull($this->collection, $criteria, 'reply', $removed_reply);
    }

    /**
     * 转换评论内容(解析图片和链接)
     */
    protected function trans_content($c){
        if(empty($c)){
            return;
        }

        $c = $this->trans_img($c);
        $c = $this->trans_link($c);
        $c = $this->trans_at($c);
        
        return $c;
    }

    /**
     * 转换图片格式
     */
    protected function trans_img($c){
        if(empty($c)){
            return;
        }
        $merge = '/\[i:(.*):\]/U';
        $c = preg_replace_callback(
            $merge,
            function($s){
                $a = explode('::', $s[1]);
                $img = ' <p class="comment-img-box" show-type="1"><img src="'.$a[0].'" alt="'.$a[1].'" title="'.$a[1].'" style="cursor: -webkit-zoom-in;" /></p>';
                return $img;
            },
            $c
        );
        return $c;
    }

    /**
     * 转换链接格式
     */
    protected function trans_link($c){
        if(empty($c)){
            return;
        }
        $merge = '/\[l:(.*):\]/U';
        $c = preg_replace_callback(
            $merge,
            function($s){
                $a = explode('::', $s[1]);
                $img = ' <a href="'.$a[0].'" title="'.$a[1].'" target="_blank" >'.$a[1].'</a> ';
                return $img;
            },
            $c
        );
        
        return $c;
    }

    /**
     * 转换@格式
     */
    protected function trans_at($c){
        if(empty($c)){
            return;
        }
        $merge = '/\[at:(.*):\]/U';
        $c = preg_replace_callback(
            $merge,
            function($s){
                $a = explode('::', $s[1]);
                $img = ' <a href="'.$a[0].'" title="'.$a[1].'" class="comment-at" >'.$a[1].'</a> ';
                return $img;
            },
            $c
        );
        
        return $c;
    }

  /**
   * 屏蔽删除
   */
  public function mark_remove($id){
    $ok = false;
    if($id){
      $ok = $this->update_set($id, array('deleted'=>1));
    }
    return $ok;
  }
	
}
