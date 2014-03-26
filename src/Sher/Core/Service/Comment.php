<?php
class Sher_Core_Service_Comment extends Sher_Core_Service_Base {
    protected static $instance;
    protected $sort_fields = array(
        'time' => array('updated_on' => -1),
        );
    /**
     * current service instance
     *
     * @return XB_Core_Service_Comment
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

}
?>