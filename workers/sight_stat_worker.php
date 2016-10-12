<?php
/**
 * 情境每日统计
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
echo "===============SIGHT_STAT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 统计方法
function begin_stat(){
  echo "Start to stat...\n";
  $sight_stat_model = new Sher_Core_Model_SightStat();
  $yesterday = (int)date('Ymd', strtotime('-1 day'));
  $month = (int)date('Ym', strtotime('-1 day'));
  $year = (int)date('Y', strtotime('-1 day'));

  //昨天周数
  $week_num = Sher_Core_Helper_Util::get_week_now(strtotime('-1 day'));
  $week = (int)((string)$year.(string)$week_num);

  $star_tmp = strtotime(sprintf("%s 00:00:00", date('Y-m-d', strtotime('-1 day'))));
  $end_tmp = strtotime(sprintf("%s 23:59:59", date('Y-m-d', strtotime('-1 day'))));

  //如果统计表存在,跳过
  $is_exist = $sight_stat_model->first(array('day'=>(int)$yesterday));
  if(empty($is_exist)){

    // 获取昨天增长数量
    // android|ios
    $current_sight_count = fetch_count($star_tmp, $end_tmp);
    $current_love_count = fetch_love_count(12, 2, $star_tmp, $end_tmp);
    $total_sight_count = fetch_count();
    $total_love_count = fetch_love_count(12, 2);


    //查询上一次所在周
    $week_sight_count = 0;
    $week_love_count = 0;
    $current_week = $sight_stat_model->first(array('week'=>$week, 'week_latest'=>1));
    if(!empty($current_week)){
      //周汇总
      $week_sight_count = $current_week['week_sight_count'];
      $week_love_count = $current_week['week_love_count'];

      //清除最后一周标记
      $sight_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
    }

    //查询上一次所在月
    $month_sight_count = 0;
    $month_love_count = 0;
    $current_month = $sight_stat_model->first(array('month'=>$month, 'month_latest'=>1));
    if(!empty($current_month)){
      //月汇总
      $month_sight_count = $current_month['month_sight_count'];
      $month_love_count = $current_month['month_love_count'];

      //清除最后一月标记
      $sight_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
    }

    $data = array(
      'day' => (int)$yesterday,
      'week' => $week,
      # 是否当前周最终统计
      'week_latest' => 1,
      'month' => $month,
      # 是否当前月最终统计
      'month_latest' => 1,

      // 当日/周/月/ 情境量、点赞量
      'day_sight_count' => $current_sight_count,
      'week_sight_count' => $current_sight_count+$week_sight_count,
      'month_sight_count' => $current_sight_count+$month_sight_count,

      'day_love_count' => $current_love_count,
      'week_love_count' => $current_love_count+$week_love_count,
      'month_love_count' => $current_love_count+$month_love_count,

      // 获取总值
      'total_sight_count' => $total_sight_count,
      'total_love_count' => $total_love_count,

    );

    $sight_stat_model->create($data);

  } // endif is_exist

  echo "End stat... \n";
}

// 获取情境量
function fetch_count($star_tmp=0, $end_tmp=0){
  $query['deleted'] = 0;
  $query['is_check'] = 1;
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }

  $scene_sight_model = new Sher_Core_Model_SceneSight();
  $count = $scene_sight_model->count($query);
  return $count;
}

// 获取情境点赞量
function fetch_love_count($type, $event, $star_tmp=0, $end_tmp=0){
    $query['type'] = $type;
    $query['event'] = $event;
  if(!empty($star_tmp) && !empty($end_tmp)){
    $query['created_on'] = array('$gte'=>$star_tmp, '$lte'=>$end_tmp);
  }
  $pusher_model = new Sher_Core_Model_Favorite();
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
echo "===============SIGHT_STAT WORKER WAKE DOWN===============\n";
echo "-------------------------------------------------\n";

// sleep 1 hour
sleep(3600);
exit(0);
