<?php
/**
 * 定时清理工作
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
echo "===============TRY CLEAN WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to clean...\n";

/**
 * 清理过期验证码
 */
$verify_mode = new Sher_Core_Model_Verify();
$page = 1;
$size = 1000;
$is_end = false;

$total = 0;

while(!$is_end){
	$query = array();
	$options = array('field'=>array('_id','phone','code','expired_on','created_on'), 'page'=>$page, 'size'=>$size);
	$list = $verify_mode->find($query, $options);
	if(empty($list)){
		echo "verify list is null,exit......\n";
		break;
	}
	$max = count($list);
  $current_time = time();
	for ($i=0; $i<$max; $i++) {
    $verify_id = (string)$list[$i]['_id'];
    if(isset($list[$i]['expired_on'])){
      if($list[$i]['expired_on']<$current_time){
        $verify_mode->remove($verify_id);
		    $total++;
        echo "success remove verify [".$verify_id."]..........\n";
      }     
    }else{ // 如果没有过期时间，根据创建时间判断
      if(($list[$i]['created_on']+600)<$current_time){
        $verify_mode->remove($verify_id);
		    $total++;
        echo "success remove verify [".$verify_id."]..........\n";
      }     
    }
		
	}
	if($max < $size){
		$is_end = true;
		echo "verify list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "clean verify expired [$total] is OK! \n";
// sleep 1 hour
sleep(600);
exit(0);
