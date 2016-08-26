#!/usr/bin/env php
<?php
/**
 * 用户统计
 * @tianshuai
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

echo "begin fetch users...\n";
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;

while(!$is_end){
	$query = array('identify.is_scene_subscribe'=>1);
	$options = array('page'=>$page, 'size'=>$size);
	$list = $user_model->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $user = $list[$i];
    if(empty($user)){
      continue;
    }
    $id = $user['_id'];
    if(!isset($user['scene_count'])){
      //$user_model->update_set($id, array('scene_count'=>0));
    }
    if(!isset($user['sight_count'])){
      //$user_model->update_set($id, array('sight_count'=>0));
    }

    $ok = true;
    //$ok = $user_model->update_set($id, array('identify.is_scene_subscribe'=>0));
    if($ok){
        echo "update scene_subscribe: $id.\n";
        $total++;
    }else{
        echo "update fail: $id.\n";
    }

	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
    $is_end = true;
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "Total count: $total \n";
echo "All user stat done.\n";

