#!/usr/bin/env php
<?php
/**
 * 检查pusher 字段 uuid重复值
 *
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

echo "check pusher ...\n";

$model = new Sher_Core_Model_Pusher();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = 0;
	$query = array();
	$options = array('field' => array('_id', 'uuid', 'created_on'), 'page'=>$page, 'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get pusher list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = (string)$list[$i]['_id'];
        $uuid = $list[$i]['_id'];
        $repeat_count = $model->count(array('uuid'=>$uuid));
        if($repeat_count>=2){
            echo "repeat uuid: $uuid.\n";
            $total++;
        }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "check pusher count: $total is OK! \n";

