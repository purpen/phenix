#!/usr/bin/env php
<?php
/**
 * 设置试用表字段
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

echo "set try fields ...\n";

$model = new Sher_Core_Model_Try();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('invented_apply_count'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get try list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    if(!isset($list[$i]['invented_apply_count'])){
		  $model->update_set($list[$i]['_id'], array('invented_apply_count'=>0));
		  echo "set try[".$list[$i]['_id']."]..........\n";
		  $total++;
    }
	}
	if($max < $size){
		echo "try list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "update try field is OK! \n";

