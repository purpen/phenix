<?php
/**
 * 各种推荐列表 (置顶帖子，首页推荐,大赛统计等)
 * @author purpen
 */
class Sher_Core_Model_DigList extends Sher_Core_Model_Base  {
	
    protected $collection = "diglist";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;

    protected $schema = array(
    	'_id'   => '',
        'name'  => '',
    	'items' => array(),
    );
	
	protected $required_fields = array('_id');
    protected $int_fields = array('type');
	
    protected $joins = array();
	
    protected function extra_extend_model_row(&$row) {
        switch($row['_id']){
          case Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT:
                $str = '校园行线下抽奖';
                break;
            case Sher_Core_Util_Constant::DIG_XUN_SEARCH_LAST_TIME:
              $str = '搜索定时统计(记录最后一次创建时间点)';
              break;
            case Sher_Core_Util_Constant::DIG_CES_PRAISE_STAT:
              $str = 'CES线下抽奖';
              break;
            case Sher_Core_Util_Constant::DIG_SIGN_EVERY_DAY_STAT:
              $str = '每日签到统计';
              break;
            default:
                $str = '未定义';
        }
        
        $row['name_info'] = $str;
    }
    
    /**
     * 增加Product投票总数/正在投票数
     */
    public function inc_fever_counter($field='items.total_count', $inc=1){
        $criteral = array('_id' => Sher_Core_Util_Constant::FEVER_COUNTER);
        return $this->inc($criteral, $field, $inc);
    }
    
    /**
     * 减少Product投票总数/正在投票数
     * 需验证，防止出现负数
     */
    public function dec_fever_counter($field='items.total_count', $force=false){
        $criteral = array('_id' => Sher_Core_Util_Constant::FEVER_COUNTER);
		if(!$force){
			$stuff = $this->find_by_id(Sher_Core_Util_Constant::FEVER_COUNTER);
			if(!isset($stuff['items'][$field]) || $stuff['items'][$field] <= 0){
				return true;
			}
		}
        return $this->dec($criteral, $field);
    }
    
    /**
     * 增加Stuff总数/精选数
     */
    public function inc_stuff_counter($field='items.total_count', $inc=1){
        $criteral = array('_id' => Sher_Core_Util_Constant::STUFF_COUNTER);
        return $this->inc($criteral, $field, $inc);
    }
    
    /**
     * 减少Stuff总数/精选数
     * 需验证，防止出现负数
     */
    public function dec_stuff_counter($field='items.total_count', $force=false){
        $criteral = array('_id' => Sher_Core_Util_Constant::STUFF_COUNTER);
		if(!$force){
			$stuff = $this->find_by_id(Sher_Core_Util_Constant::STUFF_COUNTER);
			if(!isset($stuff['items'][$field]) || $stuff['items'][$field] <= 0){
				return true;
			}
		}
        return $this->dec($criteral, $field);
    }
    
    /**
     * 添加/更新 某个target到推荐列表
     * @return true or false
     */
    public function add_dig($dig_id, $target_id, $target_type) {
		$criteral['_id'] = (string) $dig_id;
		
        // why?
        // 不使用$addToSet的目的是希望解决:
        // 能够重新将列表中的某个item重新置顶到前面,
        // 因此最简单的实现是pull后重新push
        $pushed = array('_id'=>$target_id, 'type'=>$target_type);
		
        self::$_db->pull($this->collection, $criteral, 'items', $pushed, true);
        self::$_db->push($this->collection, $criteral, 'items', $pushed, true);
		
        return true;
    }

    /**
     * 添加/更新 item --自定义
     * @return true or false
     */
    public function add_item_custom($dig_id, $item=array()) {
		  $criteral['_id'] = (string) $dig_id;
		
      // why?
      // 不使用$addToSet的目的是希望解决:
      // 能够重新将列表中的某个item重新置顶到前面,
      // 因此最简单的实现是pull后重新push
      if(empty($item)) return false;
  
      self::$_db->pull($this->collection, $criteral, 'items', $item, true);
      self::$_db->push($this->collection, $criteral, 'items', $item, true);
  
      return true;
    }

    /**
     * 删除 item --自定义
     * @return true or false
     */
    public function remove_item_custom($dig_id, $item=array()) {
      $criteral['_id'] = (string) $dig_id;
      if(empty($item)) return false;
      $pull['items'] = $item;
  
      return $this->update($criteral, array('$pull' => $pull));
    }
	
	/**
	 * 删除对象
	 */
    public function remove_item($dig_id, $target_id, $target_type) {
        $criteral['_id'] = (string) $dig_id;
        $pull['items'] = array('_id' => $target_id, 'type'=>$target_type);
		
        return $this->update($criteral, array('$pull' => $pull));
    }

	/**
	 * 获取列表
	 * 默认$size为0，返回全部列表
	 */
    public function get_items($dig_id, $size=0) {
        $row = $this->find_by_id($dig_id);
        $items = isset($row['items']) ? $row['items'] : array();
		
        if ($size) {
            return array_slice($items, -$size, $size);
        }
		
        return $items;
    }
	
    /**
     * 获取随机$size条数据
     */
    public function pick_random_items($dig_id, $size=1) {
        $row = $this->find_by_id($dig_id);
        if (empty($row) || empty($row['items'])) {
            return array();
        }
		
        $items = $row['items'];
        shuffle($items);
        $size = min($size, count($items));
		
        return array_slice($items, 0, $size);
    }
}
