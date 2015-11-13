<?php
/**
 * 试用到期自动结束
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
echo "===============TRY EXPIRED WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to check...\n";

$try_mode = new Sher_Core_Model_Try();
$page = 1;
$size = 100;
$is_end = false;

$total = 0;

while(!$is_end){
	$query = array('state'=>Sher_Core_Model_Try::STATE_PUBLISH, 'step_stat'=>1);
	$options = array('field'=>array('_id','title','state','step_stat','end_time'), 'page'=>$page, 'size'=>$size);
	$list = $try_mode->find($query, $options);
	if(empty($list)){
		echo "try list is null,exit......\n";
		break;
	}
	$max = count($list);
  $current_time = time();
	for ($i=0; $i<$max; $i++) {
    $try_id = (int)$list[$i]['_id'];
    $end_time = strtotime($list[$i]['end_time']);
    $title = $list[$i]['title'];
    if($current_time>$end_time){
      $ok = $try_mode->update_set($try_id, array('step_stat'=>2));
      if($ok){
		    $total++;
        echo "update success try step_stat [".(string)$try_id."]..........\n";
      }     
    }
		
	}
	if($max < $size){
		$is_end = true;
		echo "try begging list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "update try expired [$total] is OK! \n";
// sleep 1 hour
sleep(3600);
exit(0);
