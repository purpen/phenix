<?php
/**
 * 签到抽奖记录表
 * @author tianshuai
 */
class Sher_Core_Model_SignDrawRecord extends Sher_Core_Model_Base  {

  protected $collection = "sign_draw_record";

  # 类型
  const KIND_PAGE = 1;
  const KIND_APP = 2;

  # 允许每天抽奖次数
  const ALLOW_MAX_TIMES = 2;
	
  protected $schema = array(
    'user_id' => null,
    # 第几期抽奖 
    'target_id' => null,
    'title' => null,
    # 抽奖时间 格式: 20151206
    'day' => 0,
    # 事件：0.未中奖；1,鸟币；2.红包；3.实物；4.虚拟币(乐视会员)
    'event'  => 0,
    # 是否已分享过(分享后送一次抽奖机会)
    'is_share' => 0,
    # 抽奖次数
    'draw_times' => 1,
    # 奖品代号
    'number_id' => 0,
    # 中奖信息描述
    'desc' => null,
    # 奖品数量
    'count' => 0,
    'kind' => self::KIND_PAGE,
    # 状态：是否奖品已发放(鸟币红包及未中奖自动发放)
    'state' => 0,
    # 记录IP
    'ip' => null,
    # 用户收货地址信息
    'receipt' => array(),
    'info' => array(),
    # 来源1,web;2,wap;3.ios;4.android;5.win;6.ipad;
    'from_to' => 1,
  );

  protected $required_fields = array('user_id', 'target_id');
  protected $int_fields = array('user_id', 'is_share', 'target_id', 'kind', 'event', 'day', 'draw_times', 'number_id', 'count', 'from_to');

  protected $joins = array(
    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
  );

	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {

      switch($row['event']){
        case 0:
          $row['event_str'] = '无';
          break;
        case 1:
          $row['event_str'] = '鸟币';
          break;
        case 2:
          $row['event_str'] = '红包';
          break;
        case 3:
          $row['event_str'] = '实物';
          break;
        case 4:
          $row['event_str'] = '虚拟物';
          break;
        default:
          $row['event_str'] = '未定义';
      }

      if(isset($row['from_to'])){
        switch($row['from_to']){
          case 1:
            $row['from_label'] = 'Web';
            break;
          case 2:
            $row['from_label'] = 'Wap';
            break;
          case 3:
            $row['from_label'] = 'IOS';
            break;
          case 4:
            $row['from_label'] = 'Android';
            break;
          case 5:
            $row['from_label'] = 'Win';
            break;
          default:
            $row['from_label'] = '--';
        }
      }

    }
	
	/**
	 * 报名成功后，更新对象数量
	 */
	protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {

    }
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {

	}

  /**
   * 需要联系卖中奖用户或发放奖品的事件
   */
  public function need_contact_user_event(){
    return array(3, 4);
  } 

  /**
   * 验证用户是否有权限抽奖
   */
  public function check_can_draw($user_id, $target_id, $kind=1){

		//当前日期
		$today = (int)date('Ymd');

    //判断是否已签到
    $user_sign_model = new Sher_Core_Model_UserSign();
    $user_sign = $user_sign_model->load($user_id);

    if(empty($user_sign) || $user_sign['last_date']!=$today){
      return array('success'=>false, 'message'=>'您今天还没有签到哦~');
    }

    // 验证是否还能抽奖
    $query = array(
      'day' => $today,
      'user_id' => $user_id,
      'target_id' => $target_id,
      'kind' => (int)$kind,
    );
    $has_one = $this->first($query);
    $obj = null;
    if($has_one){
      if($has_one['draw_times'] >= self::ALLOW_MAX_TIMES){
        return array('success'=>false, 'message'=>'您今天的抽奖机会已用完~');      
      }else{
        if(!empty($has_one['event'])){
          return array('success'=>false, 'message'=>'不要太贪哦~');   
        }
        if(empty($has_one['is_share'])){
          if((int)$kind==2){
            return array('success'=>false, 'message'=>'不要太贪哦~');   
          }else{
            return array('success'=>false, 'message'=>'您今天已抽过,微信分享后赠送一次抽奖机会~');   
          }
        }
      }
      $obj = $has_one;
    }
    return array('success'=>true, 'obj'=>$obj, 'message'=>'OK');
  }
	
}

