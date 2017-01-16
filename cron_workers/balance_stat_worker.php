<?php
/**
 * 联盟账户结算每日/周/月统计
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
echo "===============ALLIANCE BALANCE STAT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";
echo "Time: ".date('Y-m-d H:i:s', time())."\n";
echo "Start to Alliance balance stating...\n";

$alliance_mode = new Sher_Core_Model_Alliance();
$balance_record_model = new Sher_Core_Model_BalanceRecord();
$balance_stat_model = new Sher_Core_Model_BalanceStat();

$n_day = '-1 day';
$yesterday = (int)date('Ymd', strtotime($n_day));
$month = (int)date('Ym', strtotime($n_day));
$year = (int)date('Y', strtotime($n_day));

//昨天周数
$week_num = Sher_Core_Helper_Util::get_week_now(strtotime($n_day));
$week = (int)((string)$year.(string)$week_num);

$star_tmp = strtotime(sprintf("%s 00:00:00", date('Y-m-d', strtotime($n_day))));
$end_tmp = strtotime(sprintf("%s 23:59:59", date('Y-m-d', strtotime($n_day))));

$page = 1;
$size = 100;
$is_end = false;

$total = 0;

while(!$is_end){
    $query = array('status' => 5);
	$options = array('field'=>array('_id', 'status', 'user_id'), 'page'=>$page, 'size'=>$size);
	$list = $alliance_mode->find($query, $options);
	if(empty($list)){
		echo "Alliance list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i<$max; $i++) {
        $alliance_id = (string)$list[$i]['_id'];
        $user_id = $list[$i]['user_id'];

        //如果统计表存在,跳过
        $is_exist = $balance_stat_model->first(array('day'=>(int)$yesterday, 'alliance_id'=>$alliance_id));
        if(!empty($is_exist)){
            echo "today is stated...\n";
            continue;
        }

        $balance_query = array('alliance_id'=>$alliance_id, 'status'=>1, 'created_on'=>array('$gte'=>$star_tmp, '$lte'=>$end_tmp));
	    $balance_options = array('field'=>array('_id','status','alliance_id'), 'page'=>1, 'size'=>1000);
        $balance_record_list = $balance_record_model->find($balance_query, $balance_options);
        if(empty($balance_record_list)){
            echo "balance_record list is empty! next..\n";
            continue;
        }

        // 获取昨天增长数量(数量／金额)
        $current_num_count = 0;
        $current_amount_count = 0;

        $balance_record_max = count($balance_record_list);
        for($j=0;$j<count($balance_record_max);$j++){
            $balance_record = $balance_record_list[$j];
            $current_amount_count = $current_amount_count + $balance_record['amount'];
            $current_num_count = $current_num_count + $balance_record['balance_count'];
        
        } // endfor

        //查询上一次所在周
        $week_num_count = 0;
        $week_amount_count = 0;
        $current_week = $balance_stat_model->first(array('week'=>$week, 'week_latest'=>1, 'alliance_id'=>$alliance_id));
        if(!empty($current_week)){
            //周汇总
            $week_num_count = $current_week['week_num_count'];
            $week_amount_count = $current_week['week_amount_count'];

            //清除最后一周标记
            $balance_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
        }

        //查询上一次所在月
        $month_num_count = 0;
        $month_amount_count = 0;
        $current_month = $balance_stat_model->first(array('month'=>$month, 'month_latest'=>1, 'alliance_id'=>$alliance_id));
        if(!empty($current_month)){
            //月汇总
            $month_num_count = $current_month['month_num_count'];
            $month_amount_count = $current_month['month_amount_count'];

            //清除最后一月标记
            $balance_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
        }

        $data = array(
            'alliance_id' => $alliance_id,
            'user_id' => $user_id,

            'day' => $yesterday,
            'week' => $week,
            # 是否当前周最终统计
            'week_latest' => 1,
            'month' => $month,
            # 是否当前月最终统计
            'month_latest' => 1,

            // 当日/周/月/ 数量、金额
            'day_num_count' => $current_num_count,
            'week_num_count' => $current_num_count+$week_num_count,
            'month_num_count' => $current_num_count+$month_num_count,

            'day_amount_count' => $current_amount_count,
            'week_amount_count' => $current_amount_count+$week_amount_count,
            'month_amount_count' => $current_amount_count+$month_amount_count,

        );

        $ok = $balance_stat_model->create($data);
        if($ok){
            echo "balance_stat created success....\n";
            $total++;
        }else{
 		    echo "balance_stat created is fail!!!!.\n"; 
        }
		
	}   // endfor
	if($max < $size){
		$is_end = true;
		echo "Alliance balance list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "Alliance balance stat count: [$total] is OK! \n";

