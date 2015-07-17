<?php
/**
 * 全文搜索
 * @author purpen
 */
class Sher_Core_Service_Search extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);
	
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
    
	/**
	 * 初始化
	 */
    public function __construct() {
        $this->text_index = new Sher_Core_Model_TextIndex();
        $this->scws = scws_new();
        $this->scws->set_charset('utf8');
		$this->scws->add_dict(ini_get("scws.default.fpath").'/dict.utf8.xdb', SCWS_XDICT_XDB);
        $bird_dict = ini_get("scws.default.fpath").'/dict.phenix.txt';
        if (is_file($bird_dict)) {
            $this->scws->add_dict($bird_dict, SCWS_XDICT_TXT);
        }
    }
	
	/**
     * 获取关键词分词结果
     */
    public function check_query_string($query_string){
    	return Sher_Core_Helper_SCWS::segment_query_word($this->scws, $query_string);
    }
    
	/**
	 * 全文搜索
	 */
    public function search($query_string, $index_name='full', $addition_criteria=array(), $options=array()) {
        if (!in_array($index_name,array('full','content','tags'))) {
            $index_name = 'full';
        }
        $query = !is_array($addition_criteria) ? array() : $addition_criteria;
        if (!empty($query_string)) {
            $query_words = Sher_Core_Helper_SCWS::segment_query_word($this->scws, $query_string);
            if (!empty($query_words)) {
				if (count($query_words) == 1) {
                    $new_query[$index_name] = $query_words[0];
                }
                else {
                    $new_query[$index_name]['$in'] = $query_words;
                }
                $new_query += $query;
            }
			$query = $new_query;
        }
        
        return $this->query_list($this->text_index, $query, $options);
    }
	
    public function __destruct() {
        $this->scws->close();
    }
    
}