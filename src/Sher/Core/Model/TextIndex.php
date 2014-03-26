<?php
/**
 * 关键词倒排索引表
 */
class Sher_Core_Model_TextIndex extends Sher_Core_Model_Base  {

    protected $collection = "text_index";

    protected $schema = array(
        'target_id' => null,
        'full' => array(),
        'tags'=> array(),
    );
    protected $joins = array(
        'user' => array('target_id' => 'Sher_Core_Model_User'),
    );

    protected $created_timestamp_fields = array('index_on');
    protected $updated_timestamp_fields = array('index_on');
    
    protected $required_fields = array('target_id');
	protected $int_fields = array('target_id');
    protected $auto_update_timestamp = true;
    
    protected function extra_extend_model_row(&$row) {
    }
    
    public function build_index($target_id,$full_words=array(),$tags=array(),$attributes=array()) {
        $criteria['target_id'] = (int)$target_id;
        $row = $attributes;
        $row['full'] = $full_words;
        $row['tags'] = $tags;
        $row['target_id'] = (int)$target_id;
        return $this->update($criteria,$row,true);
    }
}
?>