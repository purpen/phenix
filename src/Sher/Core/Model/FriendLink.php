<?php
/**
 * 友链
 * @author tianshuai
 *
 */
class Sher_Core_Model_FriendLink extends Sher_Core_Model_Base  {
	protected $collection = "friend_link";

  const KIND_FRIEND = 1;
  const KIND_PARNTER = 2;
  const KIND_OTHER = 3;
	
	protected $schema = array(
    'title' => null,
    'short_title' => null,
    'link' => null,
    'img_url' => null,
    //备注
    'user_id' => 0,
    'kind' => self::KIND_FRIEND,
    'evt' => 1,
    'stick' => 0,
    // 排序
    'sort' => 0,
		'status' => 1,
  	);

  protected $required_fields = array('title', 'user_id');

  protected $int_fields = array('status', 'user_id', 'kind', 'sort', 'stick');


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

