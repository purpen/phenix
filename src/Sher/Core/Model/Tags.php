<?php
/**
 * 标签页面
 */
class Sher_Core_Model_Tags extends Sher_Core_Model_Base  {

    protected $collection = "tag";
    protected $track_collection = 'daily_track.tag';
    
    protected $schema = array(
        'tag' => null,
        'index' => null,
        'stuffs_count'=>0,
        'albums_count'=>0,
        'search_count'=>0,
        'total' => 0,
		'subscribe_count' => 0,
        'search_on' => null,
    );

    protected $required_fields = array('tag','index');
    protected $int_fields = array('stuffs_count','albums_count','total');
    protected $auto_update_timestamp = false;
    
    
    protected function extra_extend_model_row(&$row) {
    	$row['tag_view_url'] = Lgk_Core_Helper_Url::build_url_path('app.url.tag', $row['tag']);
    }
    
    public function get_hot_tags() {
    	$options['sort'] = array('stuffs_count'=>-1);
    	$options['page'] = 1;
        $options['size'] = 300;
    	$result = $this->find(array(),$options);
    	srand();
    	$rand_array = array('s','d');
    	for($i=0;$i<count($result);$i++) {
	    	$this->extra_extend_model_row($result[$i]);
	    	$result[$i]['css_size'] = "tag_".rand(1,4);
	    	$itor = rand(0,1);
	    	$result[$i]['css_line'] = "bdr_".$rand_array[$itor];
    	}
    	return $result;
    }
    
    /**
     * 返回按照索引表的标签列表
     *
     * @param string $tags_per_index
     * @param string $tags_sort
     * @return void
     */
    public function get_tag_lookup_list($tags_per_index=40,$tags_sort = array('stuffs_count' => -1)) {
        $lookup_indexes = array('a','b','c','d','e','f','g','h','i','j',
            'k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $result = array();
        for ($i=0; $i < count($lookup_indexes); $i++) {
            $k = $lookup_indexes[$i];
            $result[$i]['index'] = $k;
            $result[$i]['up_index'] = strtoupper($k);
            $result[$i]['tags'] = $this->find(array('index' => $k),array('page' => 1,'size' => $tags_per_index,'sort' => $tags_sort));
        }
        return $result;
    }
	/**
	 * 更新
	 */
	public function dec_counter($tag,$counter_name){
		if(empty($tag) || empty($counter_name)){
			return;
		}
		if(!in_array($tag, array('stuffs_count','search_count','subscribe_count'))){
			return;
		}
		$query['tag'] = $tag;
		$row = $this->first($query);
		if(isset($row[$counter_name]) && $row[$counter_name] > 0){
			$this->dec($query,$counter_name);
		}
	}
}
?>