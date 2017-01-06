<?php
/**
 * 每日定时佣金结算
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

echo "Start to Alliance balance stating...\n";

$alliance_mode = new Sher_Core_Model_Alliance();
$balance_mode = new Sher_Core_Model_Balance();
$balance_record_model = new Sher_Core_Model_BalanceRecord();
$balance_item_model = new Sher_Core_Model_BalanceItem();
$page = 1;
$size = 100;
$is_end = false;

$total = 0;

while(!$is_end){
    $query = array('status' => 5);
	$options = array('field'=>array('_id','status','whether_balance_stat'), 'page'=>$page, 'size'=>$size);
	$list = $alliance_mode->find($query, $options);
	if(empty($list)){
		echo "Alliance list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i<$max; $i++) {
        $alliance_id = (string)$list[$i]['_id'];
        $user_id = $list[$i]['user_id'];
        // 如果正在结算中状态，则跳过，不结算
        if(!empty($list[$i]['whether_balance_stat'])){
            continue;
        }
        $balance_query = array('alliance_id'=>$alliance_id, 'status'=>0, 'stage'=>Sher_Core_Model_Balance::STAGE_FINISH);
	    $balance_options = array('field'=>array('_id','status','stage'), 'page'=>$page, 'size'=>$size);
        $balance_list = $balance_mode->find($balance_query, $balance_options);
        if(empty($balance_list)){
            echo "balance list is empty! next..\n";
            continue;
        }
        // 开始结算，并冻结联盟账户结算额
        $ok = $alliance_mode->update_set($alliance_id, array('whether_balance_stat'=>1));
        if(!$ok) {
            echo "update alliance balance_stat is fail! next....";
            continue;
        }

        $total_price = 0;
        $rows = array();
        $balance_count = count($balance_list);
        for ($j=0;$j<$balance_count;$j++){
            $item = $balance_list[$j];
            $balance_id = (string)$item['_id'];
            $price = $item['total_price'];

            $total_price += $price;

            $row = array(
                'balance_id' => $balance_id,
                'price' => $price,
            );
            array_push($rows, $row);
            
        } // endfor

        // 他建结算记录表
        $row = array(
            'balance_count' => count($rows),
            'alliance_id' => $alliance_id,
            'amount' => $total_price,
            'user_id' => $user_id,       
        );
        $ok = $balance_record_model->create($row);

        if(!$ok){
            $alliance_mode->update_set($alliance_id, array('whether_balance_stat'=>0));
            continue;
        }

        $balance_record = $balance_record_model->get_data();
        $balance_record_id = (string)$balance_record['_id'];

        // 批量创建结算明细表
        for($k=0;$k<count($rows);$k++){
            $row = array(
                'balance_id' => $rows[$k]['balance_id'],
                'balance_record_id' => $balance_record_id,
                'alliance_id' => $alliance_id,
                'amount' => $rows[$k]['price'],
                'user_id' => $user_id,
            );
            $ok = $balance_item_model->create($row);
            if($ok){
                $balance_mode->update_set($rows[$k]['balance_id'], array('status'=>1, 'balance_on'=>time()));
            }
        } // endfor

        // 更新联盟表结算数据且解冻
        $total_balance_amount = $list[$i]['total_balance_amount'] + $total_price;
        $wait_cash_amount = $list[$i]['wait_cash_amount'] + $total_price;
        $row = array(
            'total_balance_amount' => $total_balance_amount,
            'last_balance_on' => time(),
            'last_balance_amount' => $total_price,
            'whether_balance_stat' => 0,
            'wait_cash_amount' => $wait_cash_amount,
        );
        $ok = $alliance_mode->update_set($alliance_id, $row);
        if(!$ok){
            continue;
        }
        $total++;
		
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

