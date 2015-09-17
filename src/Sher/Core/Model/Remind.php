<?php
/**
 * 消息提醒
 * @author tianshuai
 */
class Sher_Core_Model_Remind extends Sher_Core_Model_Base {

    protected $collection = "remind";
	
	// 是否已读
	const READED_YES = 1;
    const READED_NO = 0;
    
    ## 事件定义
	
	# 发帖
	const EVT_POST = 1;
	# 备用
	const EVT_PUBLISH = 2;
	# 回帖
	const EVT_REPLY = 3;
	# 评价
	const EVT_COMMENT = 4;
	# 收藏
	const EVT_FAVORITE = 5;
    # 赞
    const EVT_LOVE = 6;
    # 关注
    const EVT_FOLLOW = 7;
    # 分享
    const EVT_SHARE = 8;
    # 通过审核
    const EVT_PASS = 10;
    # 被管理员推荐
    const EVT_STICK = 11;
	# 被@的
    const EVT_AT = 12;

    # 投票-支持
    const EVT_VOTE_FAVOR = 13;
    # 投票-反对
    const EVT_VOTE_OPPOSE = 14;
    # 回复评论
    const EVT_REPLY_COMMENT = 15;

    // 类型
    const KIND_TOPIC = 1; //话题
    const KIND_PRODUCT = 2; //创意
    const KIND_COMMENT = 3; //评论
    const KIND_MESSAGE = 4; //私信
    const KIND_FOLLOW = 5;  //关注
    const KIND_TRY = 6;  //产品试用
    const KIND_STUFF = 7; //产品灵感
    const KIND_SUBJECT = 10; //专题

    protected $schema = array(
        //收到提醒的人
        'user_id' => null,
		//发送人(主动方)
		's_user_id' => null,
		//是否已读
		'readed' => 0,
		//类型
		'kind' => self::KIND_TOPIC,
		//提醒内容(备用)
		'content' => null,
        //关联父ID, 如收藏，评论ID
        'related_id' => '',
        //关联id 如产品，话题
        'parent_related_id' => '',
        //提醒事件
        'evt' => self::EVT_POST,
    );
	
	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id','s_user_id','readed','kind','evt');
	
	protected $joins = array(
        'user'   =>  array('user_id'   => 'Sher_Core_Model_User'),
		's_user' =>  array('s_user_id' => 'Sher_Core_Model_User'),
	);

	/**
	 * 创建之前，更新用户count
	 */
    protected function after_save() {
        $user_id = $this->data['user_id'];
        $kind = $this->data['kind'];
        //如果是新的记录//更新用户提醒数
        if($this->insert_mode) {
            $user = new Sher_Core_Model_User();
            $user->update_counter_byinc($user_id, 'alert_count', 1);
        }
    }

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        $obj = null;
        $r_obj = null;
        $info = null;
        $kind_str = null;
        $c_type_str = null;
        switch ($row['kind']) {
            case self::KIND_TOPIC:
                $obj = &DoggyX_Model_Mapper::load_model($row['related_id'], 'Sher_Core_Model_Topic');
                $kind_str = '话题';
                break;
            case self::KIND_PRODUCT:
                $obj = &DoggyX_Model_Mapper::load_model($row['related_id'], 'Sher_Core_Model_Product');
                $kind_str = '创意产品';
                break;
            case self::KIND_COMMENT:
                $obj = &DoggyX_Model_Mapper::load_model($row['related_id'], 'Sher_Core_Model_Comment');
                if($obj){
                  switch ($obj['type']) {
                    case 2:
                      $r_obj = &DoggyX_Model_Mapper::load_model((int)$obj['target_id'], 'Sher_Core_Model_Topic');
                      $c_type_str = '话题';
                      break;
                    case 3:
                      $r_obj = &DoggyX_Model_Mapper::load_model((int)$obj['target_id'], 'Sher_Core_Model_Try');
                      $c_type_str = '新品试用';
                      break;
                    case 4:
                      $r_obj = &DoggyX_Model_Mapper::load_model((int)$obj['target_id'], 'Sher_Core_Model_Product');
                      $c_type_str = '创意产品';
                      break;
                    case 6:
                      $r_obj = &DoggyX_Model_Mapper::load_model((int)$obj['target_id'], 'Sher_Core_Model_Stuff');
                      $c_type_str = '创意灵感';
                      break;
                    case 7:
                      $r_obj = &DoggyX_Model_Mapper::load_model((int)$obj['target_id'], 'Sher_Core_Model_Albums');
                      $c_type_str = '专辑';
                      break;
                    case 10:
                      $r_obj = null;
                      $c_type_str = '专题';
                      break;
                  }
                
                }
                $kind_str = '评论';
                break;
            case self::KIND_TRY:
                //$obj = &DoggyX_Model_Mapper::load_model($row['related_id'], 'Sher_Core_Model_Try');
                break;
            case self::KIND_STUFF:
                $obj = &DoggyX_Model_Mapper::load_model($row['related_id'], 'Sher_Core_Model_Stuff');
                $kind_str = '创意灵感';
                break;
        }
      
        // 提醒信息输出
        if(!$obj){
            return;
        }
        if(!$kind_str){
            return;
        }
        if(!$row['s_user']){
            return;
        }

        switch ($row['evt']) {
            case self::EVT_POST:
                break;
            case self::EVT_PUBLISH:
                break;
            case self::EVT_REPLY:
                break;
            case self::EVT_COMMENT:
                $info = "赞了你的";
                break;
            case self::EVT_FAVORITE:
                $info = "收藏了你的";
                break;
            case self::EVT_LOVE:
                $info = "赞了你的";
                break;
            case self::EVT_REPLY_COMMENT:
                $info = "回复了你的";
                break;
			      case self::EVT_AT:
                $info = "@了你的";
                break;
        }
        
        $row['target'] = $obj;
        $row['comment_target'] = $r_obj;
        $row['comment_type_str'] = $c_type_str;
        $row['info'] = $info;
        $row['kind_str'] = $kind_str;
    }
	
	/**
	 * 展示提醒内容
     */
    public function show_message(){
        //类型
        switch ($this->data['kind']) {
            case 1:
                $str = '';
                break;
            case 2:
                $str = '';
                break;
            case 3:
                $str = '';
                break;
            default:
                $str = '';
        } 
        return $str;
    }

    /**
     * 设置已读标识
     */
    public function set_readed($id){
        return $this->update_set((string)$id, array('readed' => 1));
    }
	
}
?>
