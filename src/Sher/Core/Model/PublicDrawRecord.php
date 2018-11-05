<?php
/**
 * 公众号抽奖统计管理
 * @author tianshuai
 */
class Sher_Core_Model_PublicDrawRecord extends Sher_Core_Model_Base  {
	protected $collection = "public_draw_record";
	
	protected $schema = array(
    'uid' => null,

    'user_id' => 0,
    'type' => 1,  # 1.铟立方；2.--；
    # 抽奖次数
    'draw_count' => 0,
    # 总共次数
    'total_count' => 0,
    //备注
    'remark'  => null,
    'status' => 1,
  );

  protected $required_fields = array();

  protected $int_fields = array('status', 'user_id', 'type', 'draw_count', 'total_count');

	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// 去除 html/php标签
    if(isset($row['remark'])){
          $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
    }
	}


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

