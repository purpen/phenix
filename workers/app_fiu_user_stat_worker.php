<?php
/**
 * Fiu app用户统计
 * @author tianshuai
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION', $cfg_doggy_version);
define('DOGGY_APP_ROOT', $cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH', $cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============APP_FIU_USER_STAT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 统计方法
function begin_stat(){
  echo "Start to stat...\n";
  $app_fiu_user_stat_model = new Sher_Core_Model_AppFiuUserStat();
  $yesterday = (int)date('Ymd', strtotime('-1 day'));
  $month = (int)date('Ym', strtotime('-1 day'));
  $year = (int)date('Y', strtotime('-1 day'));

  //昨天周数
  $week_num = Sher_Core_Helper_Util::get_week_now(strtotime('-1 day'));
  $week = (int)((string)$year.(string)$week_num);

  $star_tmp = strtotime(sprintf("%s 00:00:00", date('Y-m-d', strtotime('-1 day'))));
  $end_tmp = strtotime(sprintf("%s 23:59:59", date('Y-m-d', strtotime('-1 day'))));

  //如果统计表存在,跳过
  $is_exist = $app_fiu_user_stat_model->first(array('day'=>(int)$yesterday));
  if(empty($is_exist)){

    // 获取昨天增长数量
    // android|ios
    $current_android_count = fetch_count(1, 1, $star_tmp, $end_tmp);
    $current_ios_count = fetch_count(1, 2, $star_tmp, $end_tmp);
    $total_android_count = fetch_count(1, 1);
    $total_ios_count = fetch_count(1, 2);

    // 获取注册量
    // android|ios
    $current_android_grow_count = fetch_grow_count(2, $star_tmp, $end_tmp);
    $current_ios_grow_count = fetch_grow_count(1, $star_tmp, $end_tmp);
    $total_android_grow_count = fetch_grow_count(2);
    $total_ios_grow_count = fetch_grow_count(1);

    // 获取订单信息
    // android|ios
    $current_android_order = fetch_order_count(8, $star_tmp, $end_tmp);
    $current_ios_order = fetch_order_count(7, $star_tmp, $end_tmp);
    $total_android_order = fetch_order_count(8);
    $total_ios_order = fetch_order_count(7);

    //查询上一次所在周
    $week_android_count = 0;
    $week_ios_count = 0;
    $week_android_grow_count = 0;
    $week_ios_grow_count = 0;
    $week_android_order_count = 0;
    $week_ios_order_count = 0;
    $week_android_order_money = 0;
    $week_ios_order_money = 0;
    $current_week = $app_fiu_user_stat_model->first(array('week'=>$week, 'week_latest'=>1));
    if(!empty($current_week)){
      //周汇总
      $week_android_count = $current_week['week_android_count'];
      $week_ios_count = $current_week['week_ios_count'];
      $week_android_grow_count = $current_week['week_android_grow_count'];
      $week_ios_grow_count = $current_week['week_ios_grow_count'];
      // 订单
      $week_android_order_count = $current_week['week_android_order_count'];
      $week_ios_order_count = $current_week['week_ios_order_count'];
      $week_android_order_money = $current_week['week_android_order_money'];
      $week_ios_order_money = $current_week['week_ios_order_money'];
      //清除最后一周标记
      $app_fiu_user_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
    }

    //查询上一次所在月
    $month_android_count = 0;
    $month_ios_count = 0;
    $month_android_grow_count = 0;
    $month_ios_grow_count = 0;
    $month_android_order_count = 0;
    $month_ios_order_count = 0;
    $month_android_order_money = 0;
    $month_ios_order_money = 0;
    $current_month = $app_fiu_user_stat_model->first(array('month'=>$month, 'month_latest'=>1));
    if(!empty($current_month)){
      //月汇总
      $month_android_count = $current_month['month_android_count'];
      $month_ios_count = $current_month['month_ios_count'];
      $month_android_grow_count = $current_month['month_android_grow_count'];
      $month_ios_grow_count = $current_month['month_ios_grow_count'];
      // 订单
      $month_android_order_count = $current_month['month_android_order_count'];
      $month_ios_order_count = $current_month['month_ios_order_count'];
      $month_android_order_money = $current_month['month_android_order_money'];
      $month_ios_order_money = $current_month['month_ios_order_money'];
      //清除最后一月标记
      $app_fiu_user_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
    }

    $data = array(
      'day' => (int)$yesterday,
      'week' => $week,
      # 是否当前周最终统计
      'week_latest' => 1,
      'month' => $month,
      # 是否当前月最终统计
      'month_latest' => 1,

      // 当日/周/月/ 激活量
      'day_android_count' => $current_android_count,
      'week_android_count' => $current_android_count+$week_android_count,
      'month_android_count' => $current_android_count+$month_android_count,

      'day_ios_count' => $current_ios_count,
      'week_ios_count' => $current_ios_count+$week_ios_count,
      'month_ios_count' => $current_ios_count+$month_ios_count,

      // 当日/周/月/ 注册量
      'day_android_grow_count' => $current_android_grow_count,
      'week_android_grow_count' => $current_android_grow_count+$week_android_grow_count,
      'month_android_grow_count' => $current_android_grow_count+$month_android_grow_count,

      'day_ios_grow_count' => $current_ios_grow_count,
      'week_ios_grow_count' => $current_ios_grow_count+$week_ios_grow_count,
      'month_ios_grow_count' => $current_ios_grow_count+$month_ios_grow_count,

      // 当日/周/月/ 订单数
      'day_android_order_count' => $current_android_order['count'],
      'week_android_order_count' => $current_android_order['count']+$week_android_order_count,
      'month_android_order_count' => $current_android_order['count']+$month_android_order_count,

      'day_ios_order_count' => $current_ios_order['count'],
      'week_ios_order_count' => $current_ios_order['count']+$week_ios_order_count,
      'month_ios_order_count' => $current_ios_order['count']+$month_ios_order_count,

      // 当日/周/月/ 订单金额
      'day_android_order_money' => $current_android_order['total_money'],
      'week_android_order_money' => $current_android_order['total_money']+$week_android_order_money,
      'month_android_order_money' => $current_android_order['total_money']+$month_android_order_money,

      'day_ios_order_money' => $current_ios_order['total_money'],
      'week_ios_order_money' => $current_ios_order['total_money']+$week_ios_order_money,
      'month_ios_order_money' => $current_ios_order['total_money']+$month_ios_order_money,

      // 获取总值
      'total_android_count' => $total_android_count,
      'total_ios_count' => $total_ios_count,
      'total_android_grow_count' => $total_android_grow_count,
      'total_ios_grow_count' => $total_ios_grow_count,
      // 获取订单总值
      'total_android_order_count' => $total_android_order['count'],
      'total_ios_order_count' => $total_ios_order['count'],
      'total_android_order_money' => $total_android_order['total_money'],
      'total_ios_order_money' => $total_ios_order['total_money'],
    );

    $app_fiu_user_stat_model->create($data);

  } // endif is_exist

  echo "End stat... \n";
}

// 获取激活量
function fetch_count($kind, $device, $star_tmp=0, $end_tmp=0){
  $query['kind'] = $kind;
  $query['device'] = $device;
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }

  $fiu_user_record_model = new Sher_Core_Model_FiuUserRecord();
  $count = $fiu_user_record_model->count($query);
  return $count;
}

// 获取注册量
function fetch_grow_count($from_to, $star_tmp=0, $end_tmp=0){
  $query['from_to'] = $from_to;
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }
  $pusher_model = new Sher_Core_Model_FiuPusher();
  $count = $pusher_model->count($query);
  return $count;
}

// 获取订单信息
function fetch_order_count($from_site=0, $star_tmp=0, $end_tmp=0){

  $query['status'] = array('$in'=>array(10,15,16,20));
  $query['from_app'] = 2;
  if($from_site){
    $query['from_site'] = $from_site;
  }
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['payed_date'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }

  $options = array();
  $page = 1;
  $size = 500;
  
  $order_model = new Sher_Core_Model_Orders();
  
  $is_end = false;
  $counter = 0;
  $total_money = 0;
  $options['size'] = $size;
  
  while(!$is_end){
    $options['page'] = $page;
    
    $result = $order_model->find($query, $options);
    $max = count($result);
    for($i=0; $i<$max; $i++){
      $order = $result[$i];
      $counter ++;
      $total_money += $order['pay_money'];
    }
    
    if($max < $size){
      $is_end = true;
      break;
    }
    
    $page++;
  } // end while
  return array('count'=>$counter, 'total_money'=>$total_money);

}

// 每天零晨1点以内，统计一次
$begin_time = strtotime(sprintf("%s 00:00:00", date('Y-m-d')));
$end_time = strtotime(sprintf("%s 01:00:00", date('Y-m-d')));
$now_time = time();
if($now_time>=$begin_time && $now_time<=$end_time){
  // 开始统计...
  begin_stat();
}

echo "-------------------------------------------------\n";
echo "===============APP_FIU_USER_STAT WORKER WAKE DOWN===============\n";
echo "-------------------------------------------------\n";

// sleep 1 hour
sleep(3600);
exit(0);
