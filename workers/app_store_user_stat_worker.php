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

echo "Start to stat...\n";



// 开始统计...
$user_sign_stat_model = new Sher_Core_Model_UserSignStat();
$month = (int)date('Ym');
$year = (int)date('Y');

//今天周数
$week_num = Sher_Core_Helper_Util::get_week_now();
$week = (int)((string)$year.(string)$week_num);

//如果统计表存在,跳过
$is_exist = $user_sign_stat_model->first(array('user_id'=>(int)$user_id, 'day'=>(int)$today));
if(empty($is_exist)){
  $user_kind = isset($options['user_kind']) ? (int)$options['user_kind'] : 0;

  //查询上一次所在周
  $exp_week = 0;
  $money_week = 0;
  $current_week = $user_sign_stat_model->first(array('user_id'=>(int)$user_id, 'week'=>$week, 'week_latest'=>1));
  if(!empty($current_week)){
    //周汇总
    $exp_week = (int)$current_week['week_exp_count'];
    $money_week = (int)$current_week['week_money_count'];
    //清除最后一周标记
    $user_sign_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
  }

  //查询上一次所在月
  $exp_month = 0;
  $money_month = 0;
  $current_month = $user_sign_stat_model->first(array('user_id'=>(int)$user_id, 'month'=>$month, 'month_latest'=>1));
  if(!empty($current_month)){
    //月汇总
    $exp_month = (int)$current_month['month_exp_count'];
    $money_month = (int)$current_month['month_money_count'];
    //清除最后一月标记
    $user_sign_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
    
  }

  $data = array();
  $data = array(
    'user_id' => (int)$user_id,
    'user_kind' => $user_kind,
    'day' => (int)$today,
    'week' => $week,
    # 是否当前周最终统计
    'week_latest' => 1,
    'month' => $month,
    # 是否当前月最终统计
    'month_latest' => 1,

    // 当日/周/月/获取鸟币及经验值
    'day_exp_count' => $current_exp_count,
    'week_exp_count' => $current_exp_count+$exp_week,
    'month_exp_count' => $current_exp_count+$exp_month,

    'day_money_count' => $current_money_count,
    'week_money_count' => $current_money_count+$exp_month,
    'month_money_count' => $current_money_count+$money_month,

    // 获取经验总值
    'total_exp_count' => $user_sign['exp_count'],
    // 获取鸟币数量
    'total_money_count' => $user_sign['money_count'],

    // 当日签到排行
    'sign_no' => $user_sign['last_date_no'],
    // 当日签到时间
    'sign_time' => $user_sign['last_sign_time'],
    // 连续签到天数
    'sign_times' => $user_sign['sign_times'],
    // 最高签到天数
    'max_sign_times' => $user_sign['max_sign_times'],
    'total_sign_times' => $user_sign['total_sign_times'],
  );

  $user_sign_stat_model->create($data);

} // endif is_exist



echo "End stat... \n";
// sleep 1 hour
sleep(3600);
exit(0);
