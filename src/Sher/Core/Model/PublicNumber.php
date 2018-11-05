<?php
/**
 * 公众号统计管理
 * @author tianshuai
 */
class Sher_Core_Model_PublicNumber extends Sher_Core_Model_Base  {
	protected $collection = "public_number";
	
	protected $schema = array(
    'uid' => null,
    'mark' => null,
    'nickname' => null,
    'avatar' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,
    # 类型: 1.通用；2.web/wap；3.APP;
    'type' => 1,  # 1.铟立方；2.--；
    # 关注次数
    'follow_count' => 0,
    # 邀请次数
    'invite_count' => 0,
    # 是否关注当前公号
    'is_follow' => 0, # 0.否；1.是；
    'is_draw' => 0, # 是否领取奖厉：0.否； 1.是；
    'status' => 1,
    # 用户信息
    'user_info' => array(
      'oid' => '',
      'nickname' => '',
      'avatar' => '',
      'sex' => '',
      'country' => '',
      'province' => '',
      'city' => '',
    ),
    'info' => array(
      'int_01' => 0,
      'int_02' => 0,
      'str_01' => '',
      'str_02' => '',
    ),
  );

  protected $required_fields = array('uid');

  protected $int_fields = array('status', 'user_id', 'type', 'is_follow');


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

