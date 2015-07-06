<?php
/**
 * 实验室
 * @author tianshuai
 */
class Sher_Core_Util_D3in extends Doggy_Object {
	
  // vip 价格表
	const VIP_DAY = 80; 


  /**
   * 获取实验室相应ID,价格,描述
   */
  public static function member_vip_info($evt=0, $arg=''){
    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $array = array(
      'day'=>array('item_id'=>'1', 'item_name'=>'铟立方实验室(包1天)', 'price'=>$vip_money['day']),
      'month'=>array('item_id'=>'2', 'item_name'=>'铟立方实验室VIP(包1月)', 'price'=>$vip_money['month']),
      'quarter'=>array('item_id'=>'3', 'item_name'=>'铟立方实验室VIP(包1季)', 'price'=>$vip_money['quarter']),
      'self_year'=>array('item_id'=>'4', 'item_name'=>'铟立方实验室VIP(包半年)', 'price'=>$vip_money['self_year']), 
      'year'=>array('item_id'=>'5', 'item_name'=>'铟立方实验室VIP(包1年)', 'price'=>$vip_money['year']), 
    );
    if(empty($evt)){
      return $array;
    }else{
      if(empty($arg)){
        return $array[$evt];     
      }else{
        return $array[$evt][$arg];     
      }

    }
  }


}
