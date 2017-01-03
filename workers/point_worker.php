<?php
/**
 * 初始化积分子系统
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============POINT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// ---------------------
// 初始化
$evt_type_model = new Sher_Core_Model_PointEvent();
$rows = $evt_type_model->find();
$event_types = array();
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    $event_types[$row['_id']] = $row;
}

$quota_model = new Sher_Core_Model_UserPointQuota();
$point_record_model = new Sher_Core_Model_UserPointRecord();
$point_balance_model = new Sher_Core_Model_UserPointBalance();
$point_daily_model = new Sher_Core_Model_UserPointDaily();
$user_rank_model = new Sher_Core_Model_UserRankDefine();
$user_ext_model = new Sher_Core_Model_UserExtState();

$service = Sher_Core_Service_Point::instance();


// ---------------------
// 活动事件积分转化处理
//

$evt_model = new Sher_Core_Model_UserEvent();

$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
  $query = array(
      'state' => Sher_Core_Util_Constant::EVENT_STATE_NEW,
  );
	$options = array('page'=>$page, 'size'=>$size);
	$rows = $evt_model->find($query, $options);
  $cnt = count($rows);

  for ($i = 0; $i < $cnt; $i++) {
      $row = $rows[$i];
      $record_id = $row['_id'];
      $user_id = $row['user_id'];

      $evt_code = $row['event_code'];
      $evt_type = isset($event_types[$evt_code])?$event_types[$evt_code]:null;
      if (empty($evt_type)) {
          echo "record:$record_id  unkown event_type:$evt_code, drop it.\n";
          $evt_model->mark_done($record_id);
          continue;
      }
      // found point
      $award_point_type = $evt_type['point_type'];
      $award_point_amount = $evt_type['point_amount'];
      $award_note = '奖励【'.$evt_type['name'].'】';
      if (empty($award_point_type)) {
          echo "record:$record_id event_type:$evt_code, no award_point_type  drop it.\n";
          $evt_model->mark_done($record_id);
          continue;
      }

      $daily_limit = $evt_type['daily_limit'];
      $month_limit = $evt_type['month_limit'];
      $user_quota_row = $quota_model->load($user_id);
      if (empty($user_quota_row)) {
          $quota_model->init_record($user_id);
          $user_quota_row = $quota_model->load($user_id);
      }

      // 注意： 需要按照事件发生的事件获取当日日期，不可按照当前日期
      $evt_time = $row['time'];
      $evt_day = date('Ymd', $evt_time);
      $evt_month = date('Ym', $evt_time);
      $make_point = false;
      if ($daily_limit > 0) {
          $_day_key = "d$evt_day";
          $today_cnt = isset($user_quota_row['daily_point_limit'][$_day_key][$evt_code][$award_point_type])?$user_quota_row['daily_point_limit'][$_day_key][$evt_code][$award_point_type]: 0;

          $delta = intval($daily_limit) - intval($today_cnt);
          if ($delta <= 0) {
              echo "Exceed DAILY QUOTA => User $user_id EVT: $evt_code DAY:$evt_day LIMIT: $daily_limit\n";
              $evt_model->mark_done($record_id, $make_point);
              continue;
          }
          //统计的每天上限次数而不是积分总量,已注掉by tianshuai
          if ($delta < $award_point_amount) {
              //$award_point_amount = $delta;
          }
      }
      if ($month_limit > 0) {
          // check month quota
          $_month_key = "m$evt_month";
          $this_month_cnt = isset($user_quota_row['month_point_limit'][$_month_key][$evt_code][$award_point_type])?$user_quota_row['month_point_limit'][$_month_key][$evt_code][$award_point_type]: 0;
          $month_delta = intval($month_limit) - intval($this_month_cnt);
          if ($month_delta <= 0) {
              echo "Exceed MONTHLY QUOTA => User $user_id EVT: $evt_code MONTH:$evt_month LIMIT: $month_limit\n";
              $evt_model->mark_done($record_id, $make_point);
              continue;
          }
          //统计的每月上限次数而不是积分总量,已注掉by tianshuai
          if ($month_delta < $award_point_amount) {
              //$award_point_amount = $month_delta;
          }
      }

      // CREATE AWARD POINT RECORD
      echo "MAKE AWARD RECORD, USER_ID: $user_id, TYPE: $award_point_type, AMOUNT: $award_point_amount\n";
      $ok = $service->make_transaction($user_id, $award_point_amount, $award_note,
          Sher_Core_Util_Constant::TRANS_TYPE_IN, $award_point_type, $record_id, $evt_time);
      if (!$ok) {
          echo "WARN, faild to make award, USER_ID: $user_id, TYPE: $award_point_type, AMOUNT: $award_point_amount\n";
          echo "$user_id $award_point_amount $award_note ".Sher_Core_Util_Constant::TRANS_TYPE_IN. " $award_point_type, $record_id, $evt_time\n";
          continue;
      }
      $quota_model->touch_daily_quota($evt_day, $evt_code, $award_point_type);
      $evt_model->mark_done($record_id, true);
      echo "RECORD: $record_id PROCESS DONE, ".($i+1)." / $cnt .\n";
      $total += 1;
  }

	if($cnt < $size){
		echo "evt_model list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page evt_model updated---------\n";
}
echo "Start to evt_model ....todo: $total \n";

// ---------------------
// 会员等级达标升级处理
//


$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
  $query = array();
	$options = array('page'=>$page, 'size'=>$size);
	$balance_rows = $point_balance_model->find($query, $options);
  $cnt = count($balance_rows);

  for ($i = 0; $i < $cnt; $i++) {
      $user_balance_row = & $balance_rows[$i];
      $user_id = $user_balance_row['_id'];
      $user_ext_row = $user_ext_model->load($user_id);
      if (empty($user_ext_row)) {
          $user_ext_model->init_record($user_id);
          $user_ext_row = $user_ext_model->load($user_id);
      }
      $user_ext_row = $user_ext_model->extended_model_row($user_ext_row);
      // 将当前经验积分复制到rank_point
      // fixme: DIRTY, harcode rank_point to exp
      $exp_val = $user_balance_row['balance']['exp'];
      $user_ext_model->set(array('_id' => $user_id),array('rank_point' => $exp_val));
      // 是否升级？
      $user_rank_def = $user_ext_row['user_rank'];
      if (empty($user_rank_def)) {
          echo "WARN, invalid user_rank_id. USER:$user_id, RANK_ID:".$user_ext_row['rank_id'], "\n";
          continue;
      }
      // fixme: DIRTY: hardcode point_type to exp
      if ($exp_val >= $user_rank_def['point_amount']) {
          //可以升级，获取下一个等级
          $next_rank_def = $user_rank_model->first(array('rank_id' => $user_rank_def['next_rank_id']));
          $award_point_type = isset($next_rank_def['award_point_type'])?$next_rank_def['award_point_type']:null;
          $award_point_amount = isset($next_rank_def['award_point_amount'])?$next_rank_def['award_point_amount']:0;
          echo "USER[$user_id] RANK UPGRADE AVAILABLE, next rank: ".$next_rank_def['rank_id'].' '.$next_rank_def['title'], "\n";
          // 开始升级
          if ($user_rank_def['point_amount'] > 0) {
              echo "MAKE EXP OUT:". $user_rank_def['point_amount']."\n";
              $ok = $service->make_exp_out($user_id, $user_rank_def['point_amount'], '会员升级');
          }
          else {
              $ok = true;
          }
          if ($ok) {
              $user_balance_row = $point_balance_model->load($user_id);
              $user_ext_model->set(array('_id' => $user_id), array(
                 'rank_id' => $next_rank_def['rank_id'],
                  'next_rank_id' => $next_rank_def['next_rank_id'],
                  // fixme: HARDCODE exp
                  'rank_point' => $user_balance_row['balance']['exp'],
              ));
          }
          // 积分奖励
          if (!empty($award_point_type) and $award_point_amount > 0) {
              $service->make_transaction(
                  $user_id, $award_point_amount, '升级奖励', Sher_Core_Util_Constant::TRANS_TYPE_IN,
                  $award_point_type
              );
          }
          echo "USER[$user_id] UPGARDED OK\n";
      }
      $total += 1;
  }

	if($cnt < $size){
		echo "point_balance_model list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page point_balance_model updated---------\n";
}
echo "Start to point_balance_model ....todo: $total \n";

// ---------------------
// 积分 - 按日汇总
//
$today_account_day = intval(date('Ymd'));
$this_account_month = intval(date('Ym'));

$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
  $query = array(
      'state' => Sher_Core_Util_Constant::TRANS_STATE_OK,
      'account_state' => Sher_Core_Util_Constant::POINT_ACCOUNT_STATE_NEW,
  );
	$options = array('page'=>$page, 'size'=>$size);
	$rows = $point_record_model->find($query, $options);
  $cnt = count($rows);

  for ($i = 0; $i < $cnt; $i++) {
      $row = $rows[$i];
      $user_id = $row['user_id'];
      $d = $row['d'];
      $m = $row['m'];
      $record_id = $row['_id'];
      $point_type = $row['type'];
      $point_val = $row['val'];
      $daily_id = array(
          'user_id' => $user_id,
          'day' => $d,
      );
      $user_daily_record = $point_daily_model->first(array('_id' => $daily_id));
      if (empty($user_daily_record)) {
          echo "init daily record, USER[ $user_id ] DAY: $d\n";
          $user_daily_record = array(
              '_id' => $daily_id,
              'exp' => 0,
              'money' => 0,
              'done' => false,
          );
          $point_daily_model->create($user_daily_record);
      }
      //将未记账金额誊录到汇总表中
      $point_daily_model->inc(
          array(
              '_id' => $daily_id,
          ),
          $point_type,
          $point_val
      );
      $point_record_model->mark_accounting_doing($record_id);

      echo "$record_id accounted, ".($i+1)." of $cnt\n";
      $total += 1;
  }

	if($cnt < $size){
		echo "point_record_model list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page point_record_model updated---------\n";
}
echo "Start to point_record_model ....todo: $total \n";

// ---------------------
// 积分 结帐、补帐

// 未结帐的非当日的总账

$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
  $query = array(
      '_id.day' => array(
          '$ne' => $today_account_day,
      ),
     'done' => false,
  );
	$options = array('page'=>$page, 'size'=>$size);
	$rows = $point_daily_model->find($query, $options);
  $cnt = count($rows);

  for ($i = 0; $i < $cnt; $i++) {
      $row = $rows[$i];
      $daily_id = $row['_id'];
      $user_id = $daily_id['user_id'];
      $day = $daily_id['day'];
      // re-accounting those daily records
      $record_spec = array(
          'state' => Sher_Core_Util_Constant::TRANS_STATE_OK,
          'd' => $day,
          'user_id' => $user_id,
      );
      $record_rows = $point_record_model->find($record_spec);
      $record_cnt = count($record_rows);
      for ($j = 0; $j < $record_cnt; $j++) {
          $record_row = $record_rows[$j];
          $record_id = $record_row['_id'];
          $point_type = $record_row['type'];
          $point_val = $record_row['val'];
          if ($record_row['account_state'] == Sher_Core_Util_Constant::POINT_ACCOUNT_STATE_NEW) {
              $point_type = $record_row['type'];
              // 复查是否有当日汇总记录
              $user_daily_record = $point_daily_model->first(array('_id' => $daily_id));
              if (empty($user_daily_record)) {
                  $user_daily_record = array(
                      '_id' => $daily_id,
                      'exp' => 0,
                      'money' => 0,
                      'done' => false,
                  );
                  $point_daily_model->create($user_daily_record);
              }
              $point_daily_model->inc(
                  array(
                      '_id' => $daily_id,
                  ),
                  $point_type,
                  $point_val
              );
          }
          $point_record_model->mark_accounting_done($record_id);
          echo "$record_id is accounted.\n";
      }
      $point_daily_model->set(array('_id' => $daily_id), array('done' => true));
      echo "[USER: $user_id DAY: $day] CHECKOUT DONE. ".($i+1)." of $cnt\n";
      $total += 1;
  }
	if($cnt < $size){
		echo "point_record_model list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page point_record_model updated---------\n";
}
echo "Start to point_record_model ....todo: $total \n";


echo "===========================POINT WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
// sleep 10 minute
sleep(600);
exit(0);
