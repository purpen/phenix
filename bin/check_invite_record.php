#!/usr/bin/env php
<?php
/**
 * 检查邀请人数量
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

echo "set topic add inlink-tag ...\n";

$model = new Sher_Core_Model_InviteRecord();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = time() - 15*24*60*60;
	$query = array();
  //$query['created_on'] = array('$gt'=>$time);
	$options = array('page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get invite record list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = (string)$list[$i]['_id'];
    $user_id = $list[$i]['user_id'];
    $by_user_id = $list[$i]['by_user_id'];
    $used = $list[$i]['used']; 
    $kind = $list[$i]['kind']; 
    echo "user_id: $user_id; by_user_id: $by_user_id; kind: $kind; use: $used \n";
    $total++;
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "check invite record count: $total ... \n";

