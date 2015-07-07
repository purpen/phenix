<?php
/**
 * 关键词倒排索引表
 */
class Sher_Core_Model_TextIndex extends Sher_Core_Model_Base  {

    protected $collection = "text_index";

    protected $schema = array(
        'target_id' => null,
		'type' => null,
        'full' => array(),
        'tags'=> array(),
    );

    protected $created_timestamp_fields = array('index_on');
    protected $updated_timestamp_fields = array('index_on');
    
    protected $required_fields = array('target_id', 'type');
	protected $int_fields = array('target_id', 'type');
    protected $auto_update_timestamp = true;
	
    const TYPE_PRODUCT = 1;
    const TYPE_TOPIC = 2;
	const TYPE_USER  = 3;
    const TYPE_STUFF = 4;
	
    protected function extra_extend_model_row(&$row) {
        switch ($row['type']) {
            case self::TYPE_PRODUCT:
                $row['product'] = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Product');
                break;
            case self::TYPE_TOPIC:
                $row['topic'] = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Topic');
                break;
            case self::TYPE_STUFF:
                $row['stuff'] = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Stuff');
                break;
        }
    }
    
	/**
	 * 更新索引记录
	 */
    public function build_index($target_id,$target_type,$full_words=array(),$tags=array(),$attributes=array()) {
        $criteria['target_id'] = (int)$target_id;
		$criteria['type'] = (int)$target_type;
		
        $row = $attributes;
        $row['full'] = $full_words;
        $row['tags'] = $tags;
        $row['target_id'] = (int)$target_id;
		$row['type'] = (int)$target_type;
		
        return $this->update($criteria, $row, true);
    }
	
    /**
     * 创建话题的关键词索引
     */
    public function build_topic_index($id,$full_words=array(),$tags=array(),$attributes=array()) {
        return $this->build_index($id,self::TYPE_TOPIC,$full_words,$tags,$attributes);
    }
	
    /**
     * 创建商品的关键词索引
     */
    public function build_product_index($id,$full_words=array(),$tags=array(),$attributes=array()) {
        return $this->build_index($id,self::TYPE_PRODUCT,$full_words,$tags,$attributes);
    }
    
    /**
     * 创建产品的关键词索引
     */
    public function build_stuff_index($id,$full_words=array(),$tags=array(),$attributes=array()) {
        return $this->build_index($id,self::TYPE_STUFF,$full_words,$tags,$attributes);
    }
	
    /**
     * 创建用户的关键词索引
     */
    public function build_user_index($id,$full_words=array(),$tags=array(),$attributes=array()) {
        return $this->build_index($id,self::TYPE_USER,$full_words,$tags,$attributes);
    }
}
?>