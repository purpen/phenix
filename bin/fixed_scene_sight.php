#!/usr/bin/env php
<?php
/**
 * 修复情景
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

echo "fixed scene sight ...\n";

$scene_sight_model = new Sher_Core_Model_SceneSight();
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
$users = array();
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_sight_model->find($query, $options);
	if(empty($list)){
		echo "get scene sight list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $user_id = $list[$i]['user_id'];
        array_push($users, $user_id);
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

print_r($users);
$users = array_unique($users);

for($i=0;$i<count($users);$i++){
    $user_id = $users[$i];
    $scene_count = $scene_sight_model->count(array('deleted'=>0, 'user_id'=>$user_id));
    $user = $user_model->load($user_id);

    if($user && $user['sight_count']!=$scene_count){
        $ok = $user_model->update_set($user_id, array('sight_count'=>$scene_count));
        if($ok){
            $total+=1;
            echo "fix userId: $user_id, scene_count: $scene_count.\n";
        }
    }
}



echo "fix scene sight count: $total is OK! \n";

