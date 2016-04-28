#!/usr/bin/env php
<?php
/**
 * 修复情景产品分类
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

echo "fixed scene product category_id fields ...\n";

$scene_product_model = new Sher_Core_Model_SceneProduct();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_product_model->find($query, $options);
	if(empty($list)){
		echo "get scene product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $category_id = $list[$i]['category_id'];
    switch($category_id){
    case 146:
      $new_category_id = 67;
      break;
    case 147:
      $new_category_id = 68;
      break;
    case 148:
      $new_category_id = 69;
      break;
    case 149:
      $new_category_id = 70;
      break;
    case 150:
      $new_category_id = 71;
      break;
    case 151:
      $new_category_id = 72;
      break;
    case 152:
      $new_category_id = 74;
      break;
    case 153:
      $new_category_id = 75;
      break;
    default:
      $new_category_id = 0;
    }
    if(!isset($list[$i]['category_id'])){
      $ok = true;
      //$ok = $scene_product_model->update_set($id, array('category_id'=>$new_category_id));
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

echo "fix scene product category_id fields count: $total is OK! \n";

