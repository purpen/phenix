<?php
class Sher_Core_Service_Search extends Sher_Core_Service_Base {
    protected static $instance;
    
    /**
     * current service instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Search();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->text_index = new Sher_Core_Model_TextIndex();
        $this->scws = scws_new();
        $this->scws->set_charset('utf8');
		$this->scws->add_dict(ini_get("scws.default.fpath").'/dict.utf8.xdb', SCWS_XDICT_XDB);
        $rayshe_dict = ini_get("scws.default.fpath").'/dict.rayshe.txt';
        if (is_file($rayshe_dict)) {
            $this->scws->add_dict($rayshe_dict, SCWS_XDICT_TXT);
        }
    }

    public function __destruct() {
        $this->scws->close();
    }
    
	/**
     * 获取关键词分词结果
     */
    public function check_query_string($query_string){
    	return Sher_Core_Helper_SCWS::segment_query_word($this->scws,$query_string);
    }
    
    public function search($query_string,$index_name='full',$addition_criteria=array(),$options=array()) {
        if (!in_array($index_name,array('full','content','tags'))) {
            $index_name = 'full';
        }
        $query = !is_array($addition_criteria)?array():$addition_criteria;
        if (!empty($query_string)) {
            $query_words = Sher_Core_Helper_SCWS::segment_query_word($this->scws,$query_string);
            if (!empty($query_words)) {
				if (count($query_words)==1) {
                    $new_query[$index_name] = $query_words[0];
                }
                else {
                    $new_query[$index_name]['$all'] = $query_words;
                }
                $new_query += $query;
            }
			$query = $new_query;
        }
        return $this->query_list($this->text_index,$query,$options);
    }
    
}
?>