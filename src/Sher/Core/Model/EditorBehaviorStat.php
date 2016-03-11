<?php
/**
 * 编辑推荐，精华行为记录
 * @author tianshuai
 */
class Sher_Core_Model_EditorBehaviorStat extends Sher_Core_Model_Base  {
  protected $collection = "editor_behavior_stat";

  // 类型
  const TYPE_TOPIC = 1;
  const TYPE_STUFF = 2;
  const TYPE_PRODUCT = 3;

  // 行为
  const EVT_STICK = 1;  // 推荐
  const EVT_FINE = 2; // 精选
	
	protected $schema = array(
    'target_id' => null,
    // 发布人
    'pub_user_id' => null,
    // 操作行为人
    'do_user_id' => null,
    // 类型
    'type' => self::TYPE_TOPIC,
    // 行为
    'evt' => self::EVT_STICK,
  );

  protected $required_fields = array('target_id', 'pub_user_id', 'do_user_id');

  protected $int_fields = array('type', 'evt', 'pub_user_id', 'do_user_id');

	protected $joins = array(
	  'pub_user' => array('pub_user_id' => 'Sher_Core_Model_User'),
	  'do_user' => array('do_user_id' => 'Sher_Core_Model_User'),
	);


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

