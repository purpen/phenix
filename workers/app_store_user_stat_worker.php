<?php
/**
 * 商城app用户统计
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
echo "===============APP_STORE_USER_STAT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 统计方法
function begin_stat(){
  echo "Start to stat...\n";
  $app_store_user_stat_model = new Sher_Core_Model_AppStoreUserStat();
  $yesterday = (int)date('Ymd', strtotime('-1 day'));
  $month = (int)date('Ym', strtotime('-1 day'));
  $year = (int)date('Y', strtotime('-1 day'));

  //昨天周数
  $week_num = Sher_Core_Helper_Util::get_week_now(strtotime('-1 day'));
  $week = (int)((string)$year.(string)$week_num);

  $star_tmp = strtotime(sprintf("%s 00:00:00", date('Y-m-d', strtotime('-1 day'))));
  $end_tmp = strtotime(sprintf("%s 23:59:59", date('Y-m-d', strtotime('-1 day'))));

  //如果统计表存在,跳过
  $is_exist = $app_store_user_stat_model->first(array('day'=>(int)$yesterday));
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

    //查询上一次所在周
    $week_android_count = 0;
    $week_ios_count = 0;
    $week_android_grow_count = 0;
    $week_ios_grow_count = 0;
    $current_week = $app_store_user_stat_model->first(array('week'=>$week, 'week_latest'=>1));
    if(!empty($current_week)){
      //周汇总
      $week_android_count = $current_week['week_android_count'];
      $week_ios_count = $current_week['week_ios_count'];
      $week_android_grow_count = $current_week['week_android_grow_count'];
      $week_ios_grow_count = $current_week['week_ios_grow_count'];
      //清除最后一周标记
      $app_store_user_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
    }

    //查询上一次所在月
    $month_android_count = 0;
    $month_ios_count = 0;
    $month_android_grow_count = 0;
    $month_ios_grow_count = 0;
    $current_month = $app_store_user_stat_model->first(array('month'=>$month, 'month_latest'=>1));
    if(!empty($current_month)){
      //月汇总
      $month_android_count = $current_week['month_android_count'];
      $month_ios_count = $current_week['month_ios_count'];
      $month_android_grow_count = $current_week['month_android_grow_count'];
      $month_ios_grow_count = $current_week['month_ios_grow_count'];
      //清除最后一月标记
      $app_store_user_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
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

      // 获取总值
      'total_android_count' => $total_android_count,
      'total_ios_count' => $total_ios_count,
      'total_android_grow_count' => $total_android_grow_count,
      'total_ios_grow_count' => $total_ios_grow_count,
    );

    $app_store_user_stat_model->create($data);

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

  $app_user_record_model = new Sher_Core_Model_AppUserRecord();
  $count = $app_user_record_model->count($query);
  return $count;
}

// 获取注册量
function fetch_grow_count($from_to, $star_tmp=0, $end_tmp=0){
  $query['from_to'] = $from_to;
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }
  $pusher_model = new Sher_Core_Model_Pusher();
  $count = $pusher_model->count($query);
  return $count;
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
echo "===============APP_STORE_USER_STAT WORKER WAKE DOWN===============\n";
echo "-------------------------------------------------\n";

// sleep 1 hour
sleep(3600);
exit(0);
