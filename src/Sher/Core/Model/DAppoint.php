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
	
	protected $schema = array(

    'number' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,

    // 预约项目\时间段
    // array(item_id=>'项目ID',date_id=>'日期',time_ids=>'时间段', state=>'状态');
    'items' => array(),

    // 是否是会员 0,否; 1,是;
    'is_vip' => 0,
    // 付款方式 1.在线; 2.现场
    'pay_type' => 1,

    // 是否前来参加
    'is_attend' => 1,

    // 来源
    'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,

    // 状态
		'state' => self::STATE_NORMAL,
  	);

  protected $required_fields = array('user_id');

  protected $int_fields = array('state', 'user_id', 'is_vip', 'pay_type');


	protected $joins = array(
	    'user' => array('user_id' => 'Sher_Core_Model_User'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// 去除 html/php标签
    if(isset($row['remark'])){
		  $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
    }

		// 来源
		if (isset($row['from_site'])){
			$row['from_site_label'] = $this->get_from_label($row['from_site']);
		}
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		
		return true;
	}

	/**
	 * 获取来源站点
	 */
	protected function get_from_label($site){
		switch($site){
			case Sher_Core_Util_Constant::FROM_LOCAL:
				$label = '官网';
				break;
			case Sher_Core_Util_Constant::FROM_WEIXIN:
				$label = '微信小店';
				break;
			case Sher_Core_Util_Constant::FROM_WAP:
				$label = '手机网页';
				break;
			case Sher_Core_Util_Constant::FROM_IAPP:
				$label = '手机应用';
				break;
			default:
				$label = '其他';
				break;
		}
		return $label;
	}
	
}

