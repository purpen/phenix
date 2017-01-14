#!/usr/bin/env php
<?php
/**
 * 验证话题描述是否存在
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
@require 'autoload.php';
@require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "set user sign total_sign_times...\n";

$model = new Sher_Core_Model_UserSign();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$error_total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'max_sign_times'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $max_sign_times = isset($list[$i]['max_sign_times']) ? (int)$list[$i]['max_sign_times'] : 0;
    $ok = true;
    //$ok = $model->update_set($id, array('total_sign_times'=>$max_sign_times));
    if($ok){
      echo "success user_sign_id: $id update!!!\n";
      $total++;
    }else{
      echo "faild: $id update!!!\n";
      $error_total++;
    }

    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix user_sign add total_sign_times is OK! \n";

