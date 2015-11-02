#!/usr/bin/env php
<?php
/**
 * 修复签到统计表生成唯一标识
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

echo "fix user sign stat only index ...\n";

$model = new Sher_Core_Model_UserSignStat();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$fail_total = 0;
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get user_sign_stat list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = (string)$list[$i]['_id'];
    if(!isset($list[$i]['only_index']) || empty($list[$i]['only_index'])){
      //$ok = $model->update_set($id, array('only_index'=>md5(sprintf("%s_%s", (string)$list[$i]['day'], (string)$list[$i]['user_id']))));
      if($ok){
        echo "ok: $id. \n";
        $total++;
      }else{
        echo "fail!: $id \n";
        $fail_total++;
      }

    }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix user_sign_stat add only_index fields is OK! sucess_count: $total; fail_count: $fail_total \n";

