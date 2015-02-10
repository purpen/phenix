<?php
/**
 * 评论Service
 * @author purpen
 */
class Sher_Core_Service_Comment extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
      	'latest' => array('created_on' => -1),
		'earliest' => array('created_on' => 1),
		'hotest' => array('love_count' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Comment
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Comment();
        }
        return self::$instance;
    }
	
    /**
     * 获取评论列表
     */
    public function get_comment_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Comment();
        return $this->query_list($model,$query,$option);
    }
	
    /**
     * 获取最有价值评论列表（love_count排序）
     */
    public function get_comment_lovest_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Comment();
        return $this->query_list($model,$query,$option);
    }
	
}
?>
