<?php
/**
 * 内链标签
 * @author tianshuai
 */
class Sher_Core_Model_InlinkTag extends Sher_Core_Model_Base  {
	protected $collection = "inlink_tag";
	
  
  //类型
  const KIND_ALL = 1;
  const KIND_TOPIC = 2;
  const KIND_STUFF = 3;
  const KIND_SHOP = 4;
  const KIND_VOTE = 5;
  const KIND_TRY = 6;

	protected $schema = array(
    'tag' => null,
    // 链接数组,默认取第一个
    // array()
    'links' => array(),
    'kind' => self::KIND_ALL,
    //备注
    'remark'  => null,
    'state' => 1,
    'user_id' => 0,
  	);

  protected $required_fields = array('tag');

  protected $int_fields = array('state', 'user_id', 'kind');


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

