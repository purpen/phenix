<?php
/**
 * 站点数据汇总
 * @author purpen
 */
class Sher_Core_Model_Tracker extends Sher_Core_Model_Base {
	
    ## product,user,topic,order总数
    protected $tracker_sitedata_collection = 'tracker.site_data';
	
	public $tracker_sitedata_schema = array(
		'_id' => 'frbird',
		'users_count' => 0,
		'active_users_count' => 0,
		'products_count' => 0,
		'vote_products_count' => 0,
		'onsale_products_count' => 0,
		'topics_count' => 0,
		'comments_count' => 0,
    'topic_view_count' => 0,
    'topic_true_view_count' => 0,
    'topic_love_count' => 0,
    'topic_favorite_count' => 0,
		'orders_count' => 0,
		'success_orders_count' => 0,
	);
	
    ## 每日统计相关数据
    protected $tracker_daily_stat_collection = 'tracker.daily_stat';
	
    public $tracker_daily_stat_schema = array(
        '_id' => 'day',
        'ur_count' => 0, //user_register_count,每日注册会员数
        'uv_count' => 0, //user_visit_count,每日活跃会员，即每日访问的会员数
        'updated_on' => 0,  //最后的汇总时间
    );
    
    /**
     * 重新计算匹配总数
     */
    public function remath_sitedata_counter($id='frbird', $data=array()){
      if(!empty($id) && !empty($data)){
        return self::$_db->upsert($this->tracker_sitedata_collection, array('_id' => $id), array('$set'=>$data));
      }
    }
	
    /**
     * 记录站点总数,及匹配条件下总数
     */
    public function tracker_sitedata_counter($id='frbird', $field='users_count', $cnt=0){
        $criteria = array('_id'=>$id);
		
        if(!$cnt){
			Doggy_Log_Helper::debug("Tracker site counter：$cnt ");
        	return self::$_db->inc($this->tracker_sitedata_collection, $criteria, $field, 1, true);
        }else{
        	return self::$_db->update($this->tracker_sitedata_collection, $criteria, array('$set' => array($field=>$cnt)), true);
        }
    }
	
	/**
	 * 获取匹配的记录
	 */
    public function find_tracker_sitedata($query=array(), $limit=1){
    	if($limit == 1){
    		return self::$_db->first($this->tracker_sitedata_collection, $query);
    	}else{
    		return self::$_db->find($this->tracker_sitedata_collection, $query);
    	}
    }
	
    /**
     * 删除
     */
    public function clear_sitedata_cache($query=array()){
    	if(!empty($query)){
    		return self::$_db->remove($this->tracker_sitedata_collection, $query);
    	}
    }
    
    /**
     * 获取每日统计数据
     */
    public function find_daily_stat($query=array(), $options=array()){
    	return self::$_db->find($this->tracker_daily_stat_collection, $query, $options);
    }
	
	/**
	 * 
	 */
    public function count_daily_stat($query=array()){
    	return self::$_db->count($this->tracker_daily_stat_collection, $query);
    }
    
}
?>
