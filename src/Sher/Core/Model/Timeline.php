<?php
/**
 * 实时动态信息
 * @author purpen
 */
class Sher_Core_Model_Timeline extends Sher_Core_Model_Base  {

    protected $collection = "timeline";
    
    ## 事件定义
	
	# 发帖
	const EVT_POST = 1;
	
	# 发布产品
	const EVT_PUBLISH = 2;
	
	# 回帖
	const EVT_REPLY = 3;
	
	# 评价
	const EVT_COMMENT = 4;
	
	# 收藏
	const EVT_FAVORITE = 5;
	
  # 喜欢
  const EVT_LOVE = 6;

  # 关注
  const EVT_FOLLOW = 7;

  # 分享
  const EVT_SHARE = 8;

	
	  ## 类型定义
	
    const TYPE_TOPIC = 1;
    const TYPE_PRODUCT = 2;
	  const TYPE_USER = 3;

    protected $schema = array(
      'user_id' => 0,
      'target_id' => null,
		  'type' => self::TYPE_TOPIC,
		
		  # 目标对象所属的用户(字段不是必添)
		  'target_user_id' => 0,
		
      'evt' => 0,
      'data' => array(),
    );
	
    protected $required_fields = array('user_id','target_id','evt');
    protected $int_fields = array('user_id', 'type', 'target_user_id', 'evt');
    
    protected $joins = array(
      'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
	
    /**
     * 获取扩展信息
     */
    protected function extra_extend_model_row(&$row) {
        switch ($row['evt']){
        	case self::EVT_LOVE:
                $row['label'] = '喜欢此图片';
                $row['actived'] = 'love';
                break;
        	case self::EVT_FOLLOW:
                $row['label'] = '关注了专辑';
                $row['actived'] = 'follow';
                break;
            case self::EVT_SHARE:
                $row['actived'] = 'share';
                break;
            case self::EVT_PUBLISH:
				$row['label'] = '发布了灵感';
                $row['actived'] = 'publish';
                break;
            case self::EVT_FAVORITE:
				$row['label'] = '收藏了此图片';
                $row['actived'] = 'favorite';
                break;
            case self::EVT_COMMENT:
				$row['label'] = '回应了';
                break;
            default:
                $row['label'] = "未知操作,数据错误,请通知管理员";
            break;
        }
        
        $row['created_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
    }
	
    /**
     * 添加动态事件
     */
    public function broad_events($evt, $sender, $target_id, $type, $data=array()) {
        $event['user_id'] = (int)$sender;
        $event['evt'] = (int)$evt;
        $event['target_id'] = (int)$target_id;
        $event['type'] = (int)$type;
        $event['data'] = $data;

		## 添加目标所属用户
		
		if($type == self::TYPE_TOPIC){
			$topic = & DoggyX_Model_Mapper::load_model($target_id, 'Sher_Core_Model_Topic');
			$event['target_user_id'] = $topic['user_id'];
		}
		
		if($type == self::TYPE_PRODUCT){
			$product = & DoggyX_Model_Mapper::load_model($target_id, 'Sher_Core_Model_Product');
			$event['target_user_id'] = $product['user_id'];
		}
		
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
?>
