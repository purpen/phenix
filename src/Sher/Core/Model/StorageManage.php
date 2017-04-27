<?php
/**
 * 地盘管理员
 * @author tianshuai
 */
class Sher_Core_Model_StorageManage extends Sher_Core_Model_Base  {
	protected $collection = "storage_manage";
	
	protected $schema = array(
    // 父账户ID
    'pid' => 0,
    // 子账户ID
    'cid' => 0,
    // 地盘ID
    'scene_id' => 0,
    'username' => '',
    'account' => '',
    //备注
    'remark'  => null,
		'status' => 1,
  );

  protected $required_fields = array('pid', 'cid');
  protected $int_fields = array('pid', 'cid', 'status');

	protected $joins = array(
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

