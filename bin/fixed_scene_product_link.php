#!/usr/bin/env php
<?php
/**
 * 修复情景产品关联
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

$scene_product_link_model = new Sher_Core_Model_SceneProductLink();
$scene_sight_model = new Sher_Core_Model_SceneSight();
$scene_product_model = new Sher_Core_Model_SceneProduct();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;

/**
while(!$is_end){
	$query = array('deleted'=>1);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_sight_model->find($query, $options);
	if(empty($list)){
		echo "get scene sight list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $links = $scene_product_link_model->find(array('sight_id'=>$id));
        for($j=0;$j<count($links);$j++){
            $l_id = (string)$links[$j];
            $ok = true;
            //$ok = $scene_product_link_model->remove($l_id);
            if($ok){
                $total++;
            }
        }
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
 */

/**
while(!$is_end){
	$query = array('deleted'=>1);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_product_model->find($query, $options);
	if(empty($list)){
		echo "get scene product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $links = $scene_product_link_model->find(array('product_id'=>$id));
        for($j=0;$j<count($links);$j++){
            $l_id = (string)$links[$j];
            $ok = true;
            //$ok = $scene_product_link_model->remove($l_id);
            if($ok){
                $total++;
            }
        }
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
**/

echo "fix scene product link count: $total is OK! \n";

