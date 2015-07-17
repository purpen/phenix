<?php
/**
 * 用户活跃度统计---信赖积分
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
echo "===============USER_POINT_STAT WORKER===============\n";
echo "-------------------------------------------------\n";

echo "Start to user_point stat...\n";

$point_daily_model = new Sher_Core_Model_UserPointDaily();
$user_point_stat_model = new Sher_Core_Model_UserPointStat();
$user_ext_stat_model = new Sher_Core_Model_UserExtState();
$user_point_model = new Sher_Core_Model_UserPointBalance();
$page = 1;
$size = 100;
$is_end = false;

$total = 0;

$time_special = '-1 day';
$year = (int)date('Y', strtotime($time_special));
$month = (int)date('Ym', strtotime($time_special));

//昨天的日期
$yesterday = (int)date('Ymd' , strtotime($time_special));
//昨天周数
$week_num = Sher_Core_Helper_Util::get_week_now(strtotime($time_special));
$week = (int)((string)$year.(string)$week_num);

//如果统计表存在昨天的日期,说明昨天已统计完,则退出
$is_exist = $user_point_stat_model->first(array('day'=>$yesterday));
if(!empty($is_exist)){
  echo "user point stat is done date: $yesterday. exit.... \n";
}else{
  while(!$is_end){
    // 昨日已统计的积分
    $query = array('_id.day'=>$yesterday, 'done'=>true);
    $options = array('field'=>array('_id','exp','money','done'), 'page'=>$page, 'size'=>$size);
    $list = $point_daily_model->find($query, $options);
    if(empty($list)){
      echo "point_daily list is null,exit......\n";
      break;
    }
    $max = count($list);
    echo "begining stat....... \n";
    for ($i=0; $i<$max; $i++) {
      $user_id = (int)$list[$i]['_id']['user_id'];
      $exp = (int)$list[$i]['exp'];
      $money = (int)$list[$i]['money'];
      // 遇到升级或用户鸟币消费,则跳过不统计
      if($exp<0 || $money<0){
        continue;
      }

      //查询上一次所在周
      $exp_week = 0;
      $money_week = 0;
      $current_week = $user_point_stat_model->first(array('user_id'=>$user_id, 'week'=>$week, 'week_latest'=>1));
      if(!empty($current_week)){
        //周汇总
        $exp_week = (int)$current_week['week_point_cnt'];
        $money_week = (int)$current_week['week_money_cnt'];
        //清除最后一周标记
        $user_point_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
      }

      //查询上一次所在月
      $exp_month = 0;
      $money_month = 0;
      $current_month = $user_point_stat_model->first(array('user_id'=>$user_id, 'month'=>$month, 'month_latest'=>1));
      if(!empty($current_month)){
        //月汇总
        $exp_month = (int)$current_month['month_point_cnt'];
        $money_month = (int)$current_month['month_money_cnt'];
        //清除最后一月标记
        $user_point_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
        
      }

      //查询总记录
      $user_grade = 0;
      $total_point = 0;
      $total_money = 0;
      $user_ext = $user_ext_stat_model->load($user_id);
      if($user_ext){
        $user_grade = $user_ext['rank_id'];
        $total_point = $user_ext['rank_point'];
      }

      $user_point_balance = $user_point_model->load($user_id);
      if($user_point_balance){
        $total_money = $user_point_balance['balance']['money'];
      }

      
      $data = array(
        'user_id' => (int)$user_id,
        'day' => (int)$yesterday,
        'week' => $week,
        'month' => $month,

        'day_point_cnt' => $exp,
        'week_point_cnt' => $exp_week+$exp,
        'month_point_cnt' => $exp_month+$exp,

        'day_money_cnt' => $money,
        'week_money_cnt' => $money_week+$money,
        'month_money_cnt' => $money_month+$money,

        'total_point' => $total_point,
        'total_money' => $total_money,
        'user_grade' => $user_grade,
      );

      $ok = $user_point_stat_model->create($data);
      if($ok){
        $total++;
        echo "create success id: , user_id: $user_id.. \n";
      }else{
        echo "create fail!";
      }
      
    }
    if($max < $size){
      $is_end = true;
      echo "point_daily list is end!!!!!!!!!,exit.\n";
      break;
    }
    $page++;
    echo "page [$page] updated stat---------\n";
  }

  echo "stat user_point_stat expired [$total] is OK! \n";
}


echo "-------------------------------------------------\n";
echo "===============USER_POINT_STAT WORKER END===============\n";
echo "-------------------------------------------------\n";
// sleep 1 hour
sleep(3600);
exit(0);
