<?php
class Sher_Core_Service_Base {
    protected static $empty_result = array(
        'total_rows'=>0,
        'rows'=>array(),
        'total_page' => 0,
        'current_page' => 1,
        'pager' => '',
        'next_page' => 0,
        'prev_page' => 0,
    );

    protected $sort_fields = array(
        'time' => array('created_on' => -1),
        'latest' => array('created_on' => -1),
        'hot' => array('event_count' => -1),
	);
	
	//查看最大记录数，前200页
	const MAX_OFFSET = 2000;

    public function query_list($model,$query=array(),$options=array()) {
        $result = self::$empty_result;
        // 检测是否有游标起点，计算总数时不能放入$query内
        if(isset($options['query_cursor_point'])){
            $cursor_point = $options['query_cursor_point'];
        }
		
        $cache_counter = isset($options['query_count_cache_key'])?true:false;
        if ($cache_counter) {
            $mem = Doggy_Cache_Memcached::get_cluster();
            $cache_key = $options['query_count_cache_key'];
            $cnt = $mem->get($cache_key);
            if (!$cnt) {
                // XXXX, todo, dirty hack
                if (isset($options['fake_count']) && $options['fake_count']) {
                    $cnt = $options['fake_count'];
                }
                else {
                    $cnt = $model->count($query);
                }
                $ttl = isset($options['query_count_cache_ttl'])? $options['query_count_cache_ttl']:300;
                if ($cnt) {
                    $mem->add($cache_key,$cnt,null,$ttl);
                }
            }
        }
        else {
            $cnt = $model->count($query);
        }
        
        if (!$cnt) {
            return $result;
        }

        $page = isset($options['page'])? $options['page'] : -1;
        $size = isset($options['size'])? $options['size'] : -1;
        
        $limit_start = $page > 1 ? $page*$size + 1 : 0;
        if($limit_start < self::MAX_OFFSET){
        	$max_offset = self::MAX_OFFSET;
        }else{
        	$max_offset = ceil($limit_start/self::MAX_OFFSET)*self::MAX_OFFSET;
        }
        
        // only return _id
        $options['fields'] = array('_id' => 1);
        if (isset($options['sort_field'])) {
            $sort_field = $options['sort_field'];
            if (isset($this->sort_fields[$sort_field])) {
                $options['sort'] = $this->sort_fields[$sort_field];
            }
        }
        //获取记录时放入游标起点
        if(isset($cursor_point) && !empty($cursor_point)){
        	$query['_id'] = array('$lte'=>new MongoId($cursor_point));
        	$options['page'] = -1;
        }
        $slice = $model->find($query,$options);
        if (empty($slice)) {
            return $result;
        }
        $rows = $model->extend_load_all($slice);
        $result['total_rows'] = $cnt;
        if ($page>0 && $size >0) {
        	if($cnt > $max_offset){
        		$result['total_page'] = ceil($max_offset/$size);
        	}else{
        		$result['total_page'] = ceil($cnt/$size);
        	}
            $result['current_page'] = $page;
            if ($result['total_page'] > $page) {
                $result['next_page'] = $page+1;
            }
            else {
                $result['next_page'] = 0;
            }
            if ($page > 1 && $page < $result['total_page']) {
                $result['prev_page'] = $page -1;
            }
            else {
                $result['prev_page'] = 0;
            }
        }
        $result['rows'] = &$rows;
        return $result;
    }
}
?>