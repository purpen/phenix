<?php
/**
 * 实验室预约
 * @author tianshuai
 */
class Sher_Core_Model_DAppoint extends Sher_Core_Model_Base  {
	protected $collection = "d_appoint";

  // 常量
  // 预约状态
  // 正常
  const STATE_NORMAL = 1;
  // 关闭
  const STATE_CLOSE = 0;
  // 结束 
  const STATE_OVER = 2;
  // 过期
  const STATE_EXPIRE = 3;
	
	protected $schema = array(
    'title' => null,
    // 预约项目设备ID
    'class_id' => null,
    // 设备ID
    'pid' => 0,
    //备注
    'remark'  => null,
    'user_id' => 0,

    // 预约开始/结束时间 时间戳格式--不用
    'begin_time' => 0,
    'end_time' => 0,

    // 预约项目\时间段
    // array(item_id=>'项目ID', item_name=>'项目名称',date=>'日期',tiem=>'时间段', state=>'状态');
    'items' => array(),

    // 是否是会员 0,否; 1,是;
    'is_vip' => 0,
    // 付款方式 1.现场; 2.在线
    'pay_type' => 1,

    // 状态
		'state' => self::STATE_NORMAL,
  	);

  protected $required_fields = array('class_id', 'user_id', 'begin_time', 'end_time');

  protected $int_fields = array('state', 'user_id', 'is_vip', 'pay_type', 'begin_time', 'end_time', 'class_id', 'pid');


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
		// 删除Asset
		
		return true;
	}
	
}

