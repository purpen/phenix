<?php
/**
 * 实验室预约
 * @author tianshuai
 */
class Sher_Core_Model_DAppoint extends Sher_Core_Model_Base  {
	protected $collection = "d_appoint";

  // 常量
  // 预约状态
  // 状态关闭
  const STATE_NO = 0;
  // 等待付款
  const STATE_PAY = 1;
  // 状态结束
  const STATE_OVER = 2;
  // 状态成功
  const STATE_OK = 10;
	
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

    // 是否完成付款
    'is_payed' => 0,

    // 是否前来参加
    'is_attend' => 1,

    // 是否删除
    'deleted' => 0,

    // 类型
    'kind' => 1,

    // 来源
    'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,

    // 状态
		'state' => self::STATE_PAY,
  	);

  protected $required_fields = array('user_id');

  protected $int_fields = array('state', 'user_id', 'is_vip', 'pay_type', 'deleted');


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
	 * 保存后事件
	 */
    protected function after_save() {
      // 如果是新的记录
      if($this->insert_mode){
        //更新用户表identify实验室有行为
        $user_model = new Sher_Core_Model_User();
        $user_model->update_user_identify($this->data['user_id'], 'd3in_tag', 1); 

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

	/**
	 * 关闭预约
	 */
	public function close_appoint($id, $options=Array()){
        return $this->_handle_state($id, self::STATE_NO, $options);
	}

	/**
	 * 等待付款
	 */
	public function pay_appoint($id, $options=Array()){
        return $this->_handle_state($id, self::STATE_PAY, $options);
	}

	/**
	 * 预约成功
	 */
	public function finish_appoint($id, $options=Array()){
        return $this->_handle_state($id, self::STATE_OK, $options);
	}

	/**
	 * 结束预约
	 */
	public function over_appoint($id, $options=Array()){
        return $this->_handle_state($id, self::STATE_OVER, $options);
	}
	
	/**
	 * 处理预约状态
	 */
	protected function _handle_state($id, $state, $options=Array()){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('DAppoint id is Null');
        }
        if(!isset($state)){
            throw new Sher_Core_Model_Exception('DAppoint state is Null');
        }
        
		$updated = array(
			'state' => $state,
		);

    // 取消预约
    if($state == self::STATE_NO){
    
    }

    //预约成功
    if($state == self::STATE_OK){
    
    }

    $ok = $this->update_set($id, $updated);
    if($ok){
      // 取消预约
      if($state == self::STATE_NO){
        $appoint = $this->load($id);
        if(!empty($appoint)){
          $appoint_record_model = new Sher_Core_Model_DAppointRecord();
          foreach($appoint['items'] as $k=>$v){
            foreach($v['time_ids'] as $t){
              //释放名额
              $appoint_record_model->cancel_appointed((int)$v['item_id'], (int)$v['date_id'], (int)$t, $appoint['user_id']);         
            }
          }

          // 关闭该订单
          $order_model = new Sher_Core_Model_DOrder();
          $order = $order_model->first(array('item_id'=>(string)$id, 'kind'=>Sher_Core_Model_DOrder::KIND_D3IN, 'state'=>Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT));
          if($order){
            $order_model->close_order((int)$order['_id']);
          }
        }

      }

    }
    return $ok;
	}
	
}

