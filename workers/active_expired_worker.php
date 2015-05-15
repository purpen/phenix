<?php
/**
 * 活动到期自动结束
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
echo "===============ACTIVE EXPIRED WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to check...\n";

$active_mode = new Sher_Core_Model_Active();
$page = 1;
$size = 100;
$is_end = false;

$total = 0;

while(!$is_end){
	$query = array('step_stat'=>1, 'deleted'=>0);
	$options = array('field'=>array('_id','end_time'), 'page'=>$page, 'size'=>$size);
	$list = $active_mode->find($query, $options);
	if(empty($list)){
		echo "active beginning list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $active_id = (int)$list[$i]['_id'];
    $end_time = $list[$i]['end_time'];
    $current_time = time();
    if($current_time>$end_time){
      $ok = $active_mode->update_set($active_id, array('step_stat'=>2));
      if($ok){
		    $total++;
        echo "update success active step_stat [".(string)$active_id."]..........\n";
      }     
    }
		
	}
	if($max < $size){
		$is_end = true;
		echo "active begging list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "update active expired [$total] is OK! \n";
// sleep 1 hour
sleep(3600);
exit(0);
