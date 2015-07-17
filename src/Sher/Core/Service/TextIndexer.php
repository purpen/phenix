<?php
/**
 * 创建全文索引
 * @author purpen
 */
class Sher_Core_Service_TextIndexer {
    
    protected static $instance;
    
    private $user;
    private $topic;
    private $product;
    private $stuff;
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
    
	/**
	 * 初始化
	 */
    public function __construct() {
        $this->user = new Sher_Core_Model_User();
		$this->topic = new Sher_Core_Model_Topic();
		$this->product = new Sher_Core_Model_Product();
		$this->stuff = new Sher_Core_Model_Stuff();
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
	 * 用户全文索引
	 */
    public function build_user_index($user_id) {
        $row = $this->user->find_by_id($user_id);
        $attributes = array();
        foreach (array('sex','updated_on','created_on','marital','age','state') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
		
        // 全文检索内容包括: 昵称 姓名 个人评价 城市 职业 标签 
        $full_content = $row['nickname'].' '.$row['profile']['realname'].' '.$row['summary'].' '.$row['city'].' '.$row['profile']['job'].' '.implode(' ',$row['tags']);
		
        $full_words = Sher_Core_Helper_SCWS::segment_index_word($this->scws, $full_content);
        $tags = $row['tags'];
		
        return $this->text_index->build_user_index($user_id, $full_words, $tags, $attributes);
    }
	
	/**
	 * 创建话题全文索引
	 */
    public function build_topic_index($target_id) {
        $row = $this->topic->find_by_id($target_id);
		
        $attributes = array();
        foreach (array('deleted','published','user_id','updated_on','created_on','category_id') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
		
        // 全文检索内容包括: 标题 简介 标签 类别名称
        $full_content = $row['title'].' '.$row['description']. ' '.implode(' ',$row['tags']);
        $full_words = Sher_Core_Helper_SCWS::segment_index_word($this->scws, $full_content);
		
        $tags = $row['tags'];
		
        return $this->text_index->build_topic_index($target_id, $full_words, $tags, $attributes);
    }
    
	/**
	 * 创建商品全文索引
	 */
    public function build_product_index($target_id) {
        $row = $this->product->find_by_id($target_id);
		
        $attributes = array();
        foreach (array('stage','approved','designer_id','published','updated_on','created_on','category_id') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
		
        // 全文检索内容包括: 标题 简介 标签 类别名称
        $full_content = $row['title'].' '.$row['advantage'].' '.$row['summary'].' '.implode(' ',$row['tags']);
        $full_words   = Sher_Core_Helper_SCWS::segment_index_word($this->scws, $full_content);
		
        $tags = $row['tags'];
		
        return $this->text_index->build_product_index($target_id, $full_words, $tags, $attributes);
    }
    
	/**
	 * 创建产品全文索引
	 */
    public function build_stuff_index($target_id) {
        $row = $this->stuff->find_by_id($target_id);
		
        $attributes = array();
        foreach (array('stick','featured','processed','fid','updated_on','created_on','category_id') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
		
        // 全文检索内容包括: 标题 简介 标签 类别名称
        $full_content = $row['title'].' '.$row['description'].' '.$row['brand'].' '.$row['country']. ' '.implode(' ',$row['tags']);
        $full_words = Sher_Core_Helper_SCWS::segment_index_word($this->scws, $full_content);
		
        $tags = $row['tags'];
		
        return $this->text_index->build_stuff_index($target_id, $full_words, $tags, $attributes);
    }
	
	/**
	 * 删除目标索引
	 */
    public function remove_target_index($target_id) {
        return $this->text_index->remove(array('target_id' => $target_id));
    }
	
}    
?>