<?php
/**
 * 订单辅助工具
 *
 * @package default
 * @auth tianshuai
 */
class Sher_Core_Helper_Order {

  /**
   * 可退款订单状态数组
   */
  public static function refund_order_status_arr($status=0){
    $allow_refund_status = array(
      Sher_Core_Util_Constant::ORDER_READY_REFUND,
      //Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
      //Sher_Core_Util_Constant::ORDER_EVALUATE,
      //Sher_Core_Util_Constant::ORDER_PUBLISHED,
    );
    if(empty($status)){
      return $allow_refund_status;
    }else{
      return in_array((int)$status, $allow_refund_status);
    }
  }

}

