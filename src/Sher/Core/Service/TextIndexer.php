<?php
class Sher_Core_Service_TextIndexer {
    
    protected static $instance;
    
    private $user;
    private $text_index;
    private $scws;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_TextIndexer
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_TextIndexer();
        }
        return self::$instance;
    }
    
    public function __destruct() {
        if ($this->scws) {
            $this->scws->close();
        }
    }
    
    public function __construct() {
        $this->user = new Sher_Core_Model_User();
        $this->text_index = new Sher_Core_Model_TextIndex();
        $this->scws = scws_new();
        $this->scws->set_charset('utf8');
		$this->scws->add_dict(ini_get("scws.default.fpath").'/dict.utf8.xdb',SCWS_XDICT_XDB);
        $rayshe_dict = ini_get("scws.default.fpath").'/dict.rayshe.txt';
        if (is_file($rayshe_dict)) {
            $this->scws->add_dict($rayshe_dict, SCWS_XDICT_TXT);
        }
    }
    
    public function build_user_index($user_id) {
        $row = $this->user->find_by_id($user_id);
        $attributes = array();
        foreach (array('sex','updated_on','created_on','marital','age','state') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
        //全文检索内容包括: 昵称 姓名 个人评价 城市 职业 标签 
        $full_content = $row['nickname'].' '.$row['profile']['realname'].' '.$row['summary'].' '.$row['city'].' '.$row['profile']['job'].' '.implode(' ',$row['tags']);
		
        $full_words = Sher_Core_Helper_SCWS::segment_index_word($this->scws,$full_content);
        $tags = $row['tags'];
        return $this->text_index->build_index($user_id, $full_words, $tags, $attributes);
    }
    
    public function remove_target_index($target_id) {
        return $this->text_index->remove(array('target_id' => $target_id));
    }

	
}    
?>