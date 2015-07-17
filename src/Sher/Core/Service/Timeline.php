<?php
/**
 * 动态流
 * @author purpen
 */
class Sher_Core_Service_Timeline extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
    );
    
    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Timeline
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Timeline();
        }
        return self::$instance;
    }
    
    /**
     * 获取最新动态列表
     */
    public function get_latest_list($query=array(), $options=array()){
	    $model = new Sher_Core_Model_Timeline();
		return $this->query_list($model,$query,$options);
    }
    
    /**
     * 提交话题
     */
    public function broad_topic_post($user_id, $topic_id){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_POST, $user_id, $topic_id, Sher_Core_Util_Constant::TYPE_TOPIC);
    }
    
    /**
     * 提交灵感
     */
    public function broad_stuff_post($user_id, $stuff_id){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_POST, $user_id, $stuff_id, Sher_Core_Util_Constant::TYPE_STUFF);
    }
    
    /**
     * 发布产品
     */
    public function broad_product_published($user_id, $product_id){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_PUBLISH, $user_id, $product_id, Sher_Core_Util_Constant::TYPE_PRODUCT);
    }
    
    /**
     * 关注某人
     */
    public function broad_user_following($user_id, $follow_id){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_FOLLOWING, $user_id, $follow_id, Sher_Core_Util_Constant::TYPE_USER);
    }
    
    /**
     * 被某人关注
     */
    public function broad_user_follower($follow_id, $user_id){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_FOLLOW, $follow_id, $user_id, Sher_Core_Util_Constant::TYPE_USER);
    }
    
    /**
     * 收藏某对象
     */
    public function broad_target_favorite($user_id, $target_id, $type){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_FAVORITE, $user_id, $target_id, $type);
    }
    
    /**
     * 点赞某对象
     */
    public function broad_target_love($user_id, $target_id, $type){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_LOVE, $user_id, $target_id, $type);
    }
    
    /**
     * 评论某对象
     */
    public function broad_target_comment($user_id, $target_id, $type, $data=array()){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_COMMENT, $user_id, $target_id, $type, $data);
    }
    
    /**
     * 投票某对象
     */
    public function broad_target_vote($user_id, $target_id, $type){
        $model = new Sher_Core_Model_Timeline();
        return $model->broad_events(Sher_Core_Util_Constant::EVT_VOTE, $user_id, $target_id, $type);
    }
    
}