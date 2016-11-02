<?php
/**
 * 我最近使用的标签/搜索记录
 * @author tianshuai
 */
class Sher_Core_Model_UserTags extends Sher_Core_Model_Base  {

  protected $collection = "user_tags";

    # 最近使用的标签
    const KIND_FIU = 1;
	
    protected $schema = array(
        '_id' => null,
        'kind' => self::KIND_FIU,
        'scene_tags' => array(),
        'search_tags' => array(),
    );
	
    protected $required_fields = array();
    protected $int_fields = array();
	
    
    protected function extra_extend_model_row(&$row) {

    }


    /**
     * 添加/更新 item --自定义
     * @return true or false
     */
    public function add_item_custom($user_id, $field, $item) {
		  $criteral['_id'] = (int) $user_id;
		
      // why?
      // 不使用$addToSet的目的是希望解决:
      // 能够重新将列表中的某个item重新置顶到前面,
      // 因此最简单的实现是pull后重新push
      if(empty($item)) return false;
  
      self::$_db->pull($this->collection, $criteral, $field, $item, true);
      self::$_db->push($this->collection, $criteral, $field, $item, true);
  
      return true;
    }

    /**
     * 删除 item --自定义
     * @return true or false
     */
    public function remove_item_custom($user_id, $field, $item) {
      $criteral['_id'] = (int) $user_id;
      if(empty($item)) return false;
      $pull[$field] = $item;
  
      return $this->update($criteral, array('$pull' => $pull));
    }


	
}

