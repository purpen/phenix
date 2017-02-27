<?php
/**
 * 订期清理过期未使用红包
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
echo "===============BONUS CLEAN WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";
echo "Time: ".date('Y-m-d H:i:s', time())."\n";
echo "Start to Clean Bonus ...\n";

$bonus_model = new Sher_Core_Model_Bonus();

$page = 1;
$size = 100;
$is_end = false;
$total = 0;

// 过去三个月的时间
$time = time() - 3600*24*90;


//$total_count = $bonus_model->count(array('status' => Sher_Core_Model_Bonus::STATUS_OK, 'created_on'=>array('$lt'=>$time)));
//echo "Total Count: $total_count.\n";
//exit;

while(!$is_end){
    $query = array('status' => Sher_Core_Model_Bonus::STATUS_OK, 'created_on'=>array('$lt'=>$time));
	$options = array('field'=>array('_id','status','expired_at','created_on'), 'page'=>$page, 'size'=>$size);
	$list = $bonus_model->find($query, $options);
	if(empty($list)){
		echo "Bonus list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i<$max; $i++) {
        $id = (string)$list[$i]['_id'];
        $active_mark = isset($list[$i]['active_mark']) ? $list[$i]['active_mark'] : null;
        //$ok = true;
        $ok = $bonus_model->remove($id);
        if($ok){
            $bonus_model->mock_after_remove($id, array('active_mark'=>$active_mark));
            $total++;
        }
	}   // endfor
	if($max < $size){
		$is_end = true;
		echo "Bonus list list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "Bonus clean count: [$total] is OK! \n";

