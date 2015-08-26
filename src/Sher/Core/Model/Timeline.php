<?php
/**
 * 实时动态信息
 * @author purpen
 */
class Sher_Core_Model_Timeline extends Sher_Core_Model_Base  {

    protected $collection = "timeline";
    
    protected $schema = array(
        'user_id' => 0,
        'target_id' => 0,
        
        # 目标对象所属的用户(字段不是必添)
        'target_user_id' => 0,
        
		'type' => Sher_Core_Util_Constant::TYPE_TOPIC,
        'evt'  => 0,
        
        'data' => array(),
    );
	
    protected $required_fields = array('user_id', 'target_id', 'type', 'evt');
    protected $int_fields = array('user_id', 'target_user_id', 'type', 'evt');
    
    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
	
    /**
     * 获取扩展信息
     */
    protected function extra_extend_model_row(&$row) {
        switch ($row['evt']){
            case Sher_Core_Util_Constant::EVT_POST:
				$row['label'] = '提交了';
                break;
        	case Sher_Core_Util_Constant::EVT_LOVE:
                $row['label'] = '赞过';
                break;
        	case Sher_Core_Util_Constant::EVT_FOLLOW:
                $row['label'] = '被TA关注了';
                break;
        	case Sher_Core_Util_Constant::EVT_FOLLOWING:
                $row['label'] = '关注了';
                break;
            case Sher_Core_Util_Constant::EVT_SHARE:
                $row['actived'] = '分享了';
                break;
            case Sher_Core_Util_Constant::EVT_PUBLISH:
				$row['label'] = '发布了';
                break;
            case Sher_Core_Util_Constant::EVT_FAVORITE:
				$row['label'] = '收藏了';
                break;
            case Sher_Core_Util_Constant::EVT_VOTE:
				$row['label'] = '投票了';
                break;
            case Sher_Core_Util_Constant::EVT_COMMENT:
				$row['label'] = '回应了';
                $this->_load_comment($row);
                break;
            default:
                $row['label'] = "未知操作,数据错误,请通知管理员";
            break;
        }
        
        // 获取对象
        switch ($row['type']){
            case Sher_Core_Util_Constant::TYPE_TOPIC:
				$this->_load_topic($row);
                break;
            case Sher_Core_Util_Constant::TYPE_PRODUCT:
			    $this->_load_product($row);
                break;
            case Sher_Core_Util_Constant::TYPE_STUFF:
                $this->_load_stuff($row);
                break;
            case Sher_Core_Util_Constant::TYPE_USER:
		        $this->_load_user($row);
                break;
        }
        
        $row['created_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
    }
    
    /**
     * 获取话题数据
     */
    private function _load_topic(&$row){
        $target = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Topic');
        
        $target['cover_small_url']  = isset($target['cover'])?$target['cover']['thumbnails']['mini']['view_url']:'';
        $target['cover_medium_url'] = isset($target['cover'])?$target['cover']['thumbnails']['md']['view_url']:'';
        
        $row['target'] = & $target;
    }
    
    /**
     * 获取产品数据
     */
    private function _load_product(&$row){
        $target = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Product');
        
        $target['cover_small_url']  = isset($target['cover'])?$target['cover']['thumbnails']['mini']['view_url']:'';
        $target['cover_medium_url'] = isset($target['cover'])?$target['cover']['thumbnails']['md']['view_url']:'';
        
        $row['target'] = & $target;
    }
    
    /**
     * 获取灵感数据
     */
    private function _load_stuff(&$row){
        $target = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Stuff');
        
        $target['cover_small_url']  = isset($target['cover'])?$target['cover']['thumbnails']['mini']['view_url']:'';
        $target['cover_medium_url'] = isset($target['cover'])?$target['cover']['thumbnails']['md']['view_url']:'';
        
        $row['target'] = & $target;
    }
    
    /**
     * 获取用户数据
     */
    private function _load_user(&$row){
        $target = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_User');
        $target['view_url'] = $target['home_url'];
        $target['title'] = $target['screen_name'];
        $row['target'] = & $target;
    }
    
    /**
     * 获取评论内容
     */
    private function _load_comment(&$row){
        $comment = array();
        if(isset($row['data']['comment_id'])){
            $comment = &DoggyX_Model_Mapper::load_model($row['data']['comment_id'], 'Sher_Core_Model_Comment');
        }
        $row['comment'] = & $comment;
    }
    
    /**
     * 添加动态事件
     */
    public function broad_events($evt, $sender, $target_id, $type, $data=array()){
        $event['evt']       = (int)$evt;
        $event['user_id']   = (int)$sender;
        $event['target_id'] = (int)$target_id;
        $event['type']      = (int)$type;
        $event['data']      = $data;
        
        return $this->create($event);
    }
    
    /**
     * 删除动态
     */
    public function _remove($user_id, $target_id, $evt, $type){
        $query = array(
           'user_id' => (int)$user_id,
           'target_id' => (int)$target_id,
           'evt' => $evt,
           'type' => $type
        );
		
        return $this->remove($query);
    }
    
}
