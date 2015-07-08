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

  /**
   * 预约时间数组
   */
  public static function appoint_date_arr($expire_days=3){
    $date_arr = array();
    $three_arr = array();
    $weekarray=array("日","一","二","三","四","五","六");
    $base_n = 3;
    for($i=1;$i<=(int)$expire_days;$i++){
      //下一天的日期
      $desc = sprintf("%d day", $i);
      $next_id = (int)date('Ymd', strtotime($desc));
      $next_date = date('m月d日', strtotime($desc));
      $next_week = "星期".$weekarray[(int)date('w', strtotime($desc))];
      array_push($three_arr, array('id'=>$next_id, 'date'=>$next_date, 'week'=>$next_week));

      if($i!=1 && ($i)%$base_n==0){
        array_push($date_arr, $three_arr);
        $three_arr = array();
      }
    }
    $l = $expire_days % $base_n;
    if($l){
      $other_arr = array();
      for($j=1;$j<=$l;$j++){
        $desc = sprintf("%d day", $expire_days-$l+$j);
        $next_id = (int)date('Ymd', strtotime($desc));
        $next_date = date('m月d日', strtotime($desc));
        $next_week = "星期".$weekarray[(int)date('w', strtotime($desc))];
        array_push($other_arr, array('id'=>$next_id, 'date'=>$next_date, 'week'=>$next_week));    
      }
      array_push($date_arr, $other_arr);

    }
    return $date_arr;

  }


}
