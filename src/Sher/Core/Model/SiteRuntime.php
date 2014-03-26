<?php
/**
 * 系统运行期间的数据由后台服务维护
 */
class Sher_Core_Model_SiteRuntime extends Sher_Core_Model_Base {
    protected $collection = 'site_runtime';
    protected $schema = array();
    protected $int_fields = array();
    protected $session_collection = 'session';
    /**
     * 站点实时统计数据
     * 
     * return array include follows:
     * 
     * total_published_stuffs
     * total_published_albums
     * total_comments
     * total_favorites
     * total_rates
     * total_ok_users
     * online_users
     * 
     * @return array
     */
    public function get_site_live_stats() {
        return $this->find_by_id(array('_id' => 'site_live_stats'));
    }

    /**
     * Session stats array.
     *   total => how many sessions now
     *   session_list => current page session list
     *   pos => current session cursor position
     *
     * @param string $pos cursor start position
     * @param int $size rows to move
     * @param bool $forward forward or back
     * @return array
     */
    public function get_session_stats($pos=null,$size=100,$forward=true) {
        $cnt = self::$_db->count($this->session_collection);
        $query = array();
        if (!empty($pos)) {
            $pos = DoggyX_Mongo_Db::id($pos);
            if ($forward) {
                $query['_id']['$lt'] = $pos;
            }
            else {
                $query['_id']['$gt'] = $pos;
            }
        }
        $options['page'] = 1;
        $options['size'] = $size;
        $options['sort'] = array('_id' => -1);
        $list = self::$_db->find($this->session_collection,$query,$options);
        $cur_pos = null;
        $start_pos = null;
        if ($list) {
            for ($i=0; $i < count($list); $i++) {
                if ($i == 0) {
                    $start_pos = (string) $list[$i]['_id'];
                }
                if ($list[$i]['user_id']) {
                    $list[$i]['user_url'] = Lgk_Core_Helper_Url::user_home_url($list[$i]['user_id']);
                }
                else {
                    $list[$i]['user_id'] = '游客';
                }
                $list[$i]['ip'] = long2ip($list[$i]['ip']);
                $cur_pos = (string)$list[$i]['_id'];
            }
        }
        return array('total' => $cnt,'session_list' => $list,'pos' => $cur_pos,'start' => $start_pos);
    }
}
?>