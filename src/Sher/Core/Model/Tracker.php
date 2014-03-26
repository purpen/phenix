<?php
/**
 * 站点数据汇总
 */
class Sher_Core_Model_Tracker extends Sher_Core_Model_Base {
    //stuff,user总数
    protected $track_sitedata_collection = 'daily_track.site_data';
    //每日统计相关数据
    protected $track_stat_collection = 'daily_track.stat';
    /*
    public $daily_track_stat_schema = array(
        '_id' => 'day',
        'ur_count' => 0, //user_register_count,每日注册会员数
        'uv_count' => 0, //user_visit_count,每日活跃会员，即每日访问的会员数
        'updated_on' => time  //最后的汇总时间
    );
    */
    
    
    //记录stuff总数,及匹配条件下总数
    public function track_sitedata_counter($id='c0_stuff',$cnt=0) {
        $query['name'] = (string)$id;
        $query['updated_on'] = (int)date('Ymd',time());
        if(!$cnt){
        	self::$_db->inc($this->track_sitedata_collection,$query,'stuff_count',1,true);
        }else{
        	self::$_db->update($this->track_sitedata_collection,$query,array('$set'=>array('stuff_count'=>$cnt)),true);
        }
    }
    //获取匹配的记录
    public function find_track_sitedata($query=array(),$limit=1){
    	if($limit == 1){
    		return self::$_db->first($this->track_sitedata_collection,$query);
    	}else{
    		return self::$_db->find($this->track_sitedata_collection,$query);
    	}
    }
    //删除
    public function clear_sitedata_cache($query=array()){
    	if(!empty($query)){
    		return self::$_db->remove($this->track_sitedata_collection,$query);
    	}
    }
    
    //获取每日统计数据
    public function find_daily_stat($query=array(),$options=array()){
    	return self::$_db->find($this->track_stat_collection, $query, $options);
    }
    public function count_daily_stat($query=array()){
    	return self::$_db->count($this->track_stat_collection, $query);
    }
    
}
?>