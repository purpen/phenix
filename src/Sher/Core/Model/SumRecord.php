<?php
/**
 * 数量统计表
 * @author tianshuai
 */
class Sher_Core_Model_SumRecord extends Sher_Core_Model_Base  {

  protected $collection = "sum_record";

  //省份
  const TYPE_PRO = 1;
  //大学
  const TYPE_COLLEGE = 2;
  //微信分享数/商品/灵感/话题
  const TYPE_WXSHARE = 3;
  //微信分享数/专题
  const TYPE_SUBJECT_SHARE = 4;

  //属性
  // 话题
  const KIND_TOPIC = 1;
  // 产品
  const KIND_PRODUCT = 2;
  // 灵感
  const KIND_STUFF = 3;
  // 专题
  const KIND_SUBJECT = 4;

	
    protected $schema = array(
      # 关联ID
		  'target_id'   => 0,
      # 类型
      'type' => self::TYPE_PRO,
      # kind属性
      'kind' => 0,

      'count' => 0,
      # 浏览数
      'view_count' => 0,
      # 十万火计2数量
      'match2_count' => 0,
      # 十万火计2 人气数量(大学点赞)
      'match2_love_count' => 0,
      # id数组 备
      'items' => array(),
    );
	
    protected $joins = array();
	
    protected $required_fields = array('target_id','type');
    protected $int_fields = array('type','count','match2_count','kind','match2_love_count','view_count');
    protected $counter_fields = array('count','match2_count','match2_love_count','view_count');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }

	/**
	 * 增加计数
	 */
	public function increase_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}

	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($count_name,$id=null,$force=false){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$d = $this->find_by_id((string)$id);
			if(!isset($d[$count_name]) || $d[$count_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $count_name);
	}

  /**
   * 增加新记录
   */
  public function add_record($target_id, $filed_name='count', $type=self::TYPE_PRO, $kind=0){
    if(empty($kind)){
      $query = array('target_id'=>$target_id, 'type'=>$type);
    }else{
      $query = array('target_id'=>$target_id, 'type'=>$type, 'kind'=>(int)$kind);
    }
    $d = $this->first($query);
    if($d){
      $this->increase_counter($filed_name, 1, $d['_id']);
    }else{
      $query[$filed_name] = 1;
      $this->create($query);
    }
  }

  /**
   * 增加记录数--大量的
   */
  public function multi_add_record($target_id, $filed_name='count', $num=1, $type=self::TYPE_PRO){
    $query = array('target_id'=>$target_id, 'type'=>(int)$type);
    $d = $this->first($query);
    if($d){
      $this->increase_counter($filed_name, (int)$num, $d['_id']);
    }else{
      $query[$filed_name] = (int)$num;
      $this->create($query);
    }

  }

  /**
   * 减少记录数
   */
  public function down_record($target_id, $filed_name='count', $type=self::TYPE_PRO){
    $query = array('target_id'=>$target_id, 'type'=>$type);
    $d = $this->first($query);
    if($d){
      $this->dec_counter($filed_name, $d['_id']);
    }

  }

  /**
   * 减少记录数--大量的
   */
  public function multi_down_record($target_id, $filed_name='count', $num=1, $type=self::TYPE_PRO){
    $query = array('target_id'=>$target_id, 'type'=>(int)$type);
    $d = $this->first($query);
    if($d){
      $count = isset($d[$filed_name])?(int)$d[$filed_name]:0;
      $count = $count - $num;
      if($count>=0){
        $this->update_set((string)$d['_id'], array((string)$filed_name => $count));
      }
    }

  }
	
}

