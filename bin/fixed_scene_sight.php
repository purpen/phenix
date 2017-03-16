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
$product_model = new Sher_Core_Model_Product();
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
        $product_query = array();
        $product_query['is_product'] = 0;
        $user_id = empty($list[$i]['user_id']) ? 0: $list[$i]['user_id'];
        $is_product = !empty($list[$i]['product']) ? true : false;
        if($is_product){
            for($j=0;$j<count($list[$i]['product']);$j++){
                $product_id = isset($list[$i]['product'][$j]['id']) ? (int)$list[$i]['product'][$j]['id'] : 0;
                if(empty($product_id)) continue;
                $product = $product_model->load($product_id);
                if(empty($product)) continue;
                if($product['stage']==9){
                    $product_query['is_product'] = 1;
                    break;                
                }
            }
        }
        //$scene_sight_model->update_set($id, $product_query);
        //if($user_id) array_push($users, $user_id);
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

$users = array_unique($users);

print_r($users);

foreach($users as $v){
    $user_id = $v;
    $scene_count = $scene_sight_model->count(array('deleted'=>0, 'user_id'=>$user_id));
    $user = $user_model->load($user_id);

    if($user && $user['sight_count']!=$scene_count){
        $ok = true;
        //$ok = $user_model->update_set($user_id, array('sight_count'=>$scene_count));
        if($ok){
            $total+=1;
            echo "fix userId: $user_id, scene_count: $scene_count.\n";
        }
    }
}


echo "fix scene sight count: $total is OK! \n";

