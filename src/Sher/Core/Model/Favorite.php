<?php
/**
 * 收藏/喜欢 Model
 * @author purpen
 */
class Sher_Core_Model_Favorite extends Sher_Core_Model_Base  {

    protected $collection = "favorite";
	
	// 类型
	const TYPE_PRODUCT = 1;
	const TYPE_TOPIC = 2;
	
	// event
	const EVENT_FAVORITE = 1;
	const EVENT_LOVE = 2;
	
    protected $schema = array(
    	'user_id' => null,
    	'target_id' => null,
		'tags' => array(),
        'private'=> 0,
        'type'   => self::TYPE_TOPIC,
		'event'  => self::EVENT_FAVORITE,
    );
	
    protected $joins = array(
        'user' =>   array('user_id' => 'Sher_Core_Model_User'),
	);
	
    protected $required_fields = array('user_id', 'target_id');
    protected $int_fields = array('user_id','target_id','private','type', 'event');
	
	
    protected function before_save(&$data) {
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',strip_tags($data['tags']))));
        }
    }
	
    protected function before_update(&$data) {
        $this->before_save($data);
    }
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
        switch ($row['type']) {
            case self::TYPE_PRODUCT:
                $row['product'] = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Product');
                break;
            case self::TYPE_TOPIC:
                $row['topic'] = &DoggyX_Model_Mapper::load_model($row['target_id'], 'Sher_Core_Model_Topic');
                break;
        }
		
        $row['tag_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
    }

	/**
	 * 关联事件
	 */
    protected function after_save() {
		$type = $this->data['type'];
		$event = $this->data['event'];
		
		if ($event == self::EVENT_FAVORITE){
			$field = 'favorite_count';
		}elseif ($event == self::EVENT_LOVE){
			$field = 'love_count';
		}
		switch($type){
			case self::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$model->increase_counter($field, 1, (int)$this->data['target_id']);
				break;
			case self::TYPE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$model->inc_counter($field, 1, (int)$this->data['target_id']);
				break;
		}
    }
	
    /**
     * 添加到收藏
     */
    public function add_favorite($user_id, $target_id, $info=array()) {
		$info['user_id']   = (int)$user_id;
        $info['target_id'] = (int)$target_id;
		$info['type'] = (int)$info['type'];
		$info['event'] = self::EVENT_FAVORITE;
		
        return $this->apply_and_save($info);
    }
	
    /**
     * 检测是否收藏
     */
    public function check_favorite($user_id, $target_id, $type){
		$query['user_id'] = (int)$user_id;
        $query['target_id'] = (int)$target_id;
		$query['type'] = (int)$type;
		$query['event'] = self::EVENT_FAVORITE;
		
        $result = $this->count($query);
		
        return $result>0?true:false;
    }

    /**
     * 删除收藏(只允许移除本人的收藏)
     */
    public function remove_favorite($user_id, $target_id, $type){
		$query['user_id'] = (int)$user_id;
        $query['target_id'] = (int)$target_id;
		$query['type'] = (int)$type;
		$query['event'] = self::EVENT_FAVORITE;
		
        return $this->remove($query);
    }
	
    /**
     * 检测是否喜欢
     */
	public function check_loved($user_id, $target_id,$type){
		$query['user_id'] = (int) $user_id;
        $query['target_id'] = (int) $target_id;
		$query['type'] = (int)$type;
		$query['event'] = self::EVENT_LOVE;
		
        $result = $this->count($query);
		
        return $result>0?true:false;
	}
	
    /**
     * 添加到喜欢、赞
     */
    public function add_love($user_id, $target_id, $info=array()) {		
		$info['user_id']   = (int) $user_id;
        $info['target_id'] = (int) $target_id;
		$info['type'] = (int)$info['type'];
		$info['event']     = self::EVENT_LOVE;
		
        return $this->apply_and_save($info);
    }
	
	/**
	 * 取消喜欢
	 */
	public function cancel_love($user_id, $target_id,$type){
		$query['user_id'] = (int)$user_id;
        $query['target_id'] = (int)$target_id;
		$query['type'] = (int)$type;
		$query['event']  = self::EVENT_LOVE;
		
        return $this->remove($query);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($user_id, $target_id, $type, $event) {
		if (empty($user_id) || empty($target_id) || empty($type) || empty($event)){
			throw new Sher_Core_Model_Exception('删除后关联失败！');
		}
		
		if ($event == self::EVENT_FAVORITE){
			$field = 'favorite_count';
		}elseif ($event == self::EVENT_LOVE){
			$field = 'love_count';
		}
		
		switch($type){
			case self::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$model->dec_counter($field, (int)$target_id);
				break;
			case self::TYPE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$model->dec_counter($field, (int)$target_id);
				break;
		}
	}
	
}
?>