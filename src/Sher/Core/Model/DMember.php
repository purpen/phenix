<?php
/**
 * 实验室会员
 * @author tianshuai
 */
class Sher_Core_Model_DMember extends Sher_Core_Model_Base  {
	protected $collection = "d_member";

  // 类型
  const KIND_D3IN = 1;
  const KIND_OTHER = 2;

  // 状态
  const STATE_NO = 0;
  const STATE_OK = 1;

  // 送鸟币间隔天数
  const MONEY_DAYS = 7;
	
	protected $schema = array(
    // 用户ID
    '_id' => null,
    'kind' => self::KIND_D3IN,

    // 会员有效期
    'begin_time' => 0,
    'end_time' => 0,

    // 最近一次充值金额
    'last_price' => 0,
    // 累计充值金额
    'total_price' => 0,

    //备注
    'remark'  => null,
		'state' => self::STATE_OK,
  );

  protected $int_fields = array('state', 'kind', 'begin_time', 'end_time');
  protected $float_fields = array('last_price', 'total_price');


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

