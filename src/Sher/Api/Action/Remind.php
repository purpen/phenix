<?php
/**
 * 提醒
 * @author tianshuai 
 */
class Sher_Api_Action_Remind extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
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
		
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'user_id'=>1, 's_user_id'=>1, 'readed'=>1, 'content'=>1, 'related_id'=>1,
		  'parent_related_id'=>1, 'evt'=>1, 'created_on'=>1, 'updated_on'=>1, 'created_at'=>1,
          's_user'=>1, 'kind'=>1, 'kind_str'=>1, 'info'=>1, 'from_to'=>1,
		);
		
		// 查询条件
		
        $query['user_id'] = $user_id;
        $query['from_to'] = 2;
		if($kind){
			$query['kind'] = $kind;
		}
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'time';
				break;
            default:
				$options['sort_field'] = 'time';
		}
		
		// 开启查询
		$service = Sher_Core_Service_Remind::instance();
		$result = $service->get_remind_list($query, $options);
		$remind_model = new Sher_Core_Model_Remind();
		
		foreach($result['rows'] as $k => $v){
            $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
            $s_user = null;
            if($result['rows'][$k]['s_user']){
                $s_user = array();
                $s_user['_id'] = $result['rows'][$k]['s_user']['_id'];
                $s_user['nickname'] = $result['rows'][$k]['s_user']['nickname'];
                $s_user['avatar_url'] = isset($result['rows'][$k]['s_user']['medium_avatar_url']) ? $result['rows'][$k]['s_user']['medium_avatar_url'] : null;
            }
            $result['rows'][$k]['send_user'] = $s_user;

            $user = null;
            if($result['rows'][$k]['user']){
                $user = array();
                $user['_id'] = $result['rows'][$k]['user']['_id'];
                $user['nickname'] = $result['rows'][$k]['user']['nickname'];
                $user['avatar_url'] = isset($result['rows'][$k]['user']['medium_avatar_url']) ? $result['rows'][$k]['user']['medium_avatar_url'] : null;
            }
            $result['rows'][$k]['revice_user'] = $user;

            $target = null;
            if($result['rows'][$k]['target']){
                $target = array();
                switch($result['rows'][$k]['kind']){
                    case Sher_Core_Model_Remind::KIND_COMMENT:
                        $target['_id'] = (string)$result['rows'][$k]['target']['_id'];
                        $target['content'] = $result['rows'][$k]['target']['content'];
                        break;
                    case Sher_Core_Model_Remind::KIND_SIGHT:
                        $target['_id'] = $result['rows'][$k]['target']['_id'];
                        $target['content'] = $result['rows'][$k]['target']['title'];
                        $target['cover_url'] = $result['rows'][$i]['target']['cover']['thumbnails']['mini']['view_url'];
                        break;
                    default:
                        $target = null;
                }
            
            }
            $result['rows'][$k]['target_obj'] = $target;

            $comment_target = null;
            if($result['rows'][$k]['comment_target']){
                $comment_target = array();
                $comment_target['_id'] = $result['rows'][$k]['comment_target']['_id'];
                $comment_target['content'] = $result['rows'][$k]['comment_target']['title'];
                $comment_target['cover_url'] = isset($result['rows'][$k]['comment_target']['cover']['thumbnails']['mini']['view_url']) ? $result['rows'][$k]['comment_target']['cover']['thumbnails']['mini']['view_url'] : null;
            }
            $result['rows'][$k]['comment_target_obj'] = $comment_target;

            $result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['created_on']);

            $is_read = isset($result['rows'][$k]['readed'])?$result['rows'][$k]['readed']:0;
            if(empty($is_read)){
              # 更新已读标识
              $remind_model->set_readed($result['rows'][$k]['_id']);
            }
		}   // endfor

        //清空提醒数量
        if($page==1){
          $user_model = new Sher_Core_Model_User();
          $user = $user_model->load($user_id);
          if($user && isset($user['counter']['fiu_alert_count']) && $user['counter']['fiu_alert_count']>0){
            $user_model->update_counter($user_id, 'fiu_alert_count');
          }
        }
		
		// 过滤多余属性
        $filter_fields  = array('user', 's_user', 'target', 'comment_target', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		return $this->api_json('请求成功', 0, $result);
	}

}

