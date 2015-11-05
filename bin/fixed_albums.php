#!/usr/bin/env php
<?php
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION', $cfg_doggy_version);
define('DOGGY_APP_ROOT', $cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH', $cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "Prepare to build albums fields index...\n";
$album_model = new Sher_Core_Model_Albums();
$album_shop_model = new Sher_Core_Model_Albumshop();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'comment_count', 'product_count'), 'page'=>$page, 'size'=>$size);
	$list = $album_model->find($query, $options);
	if(empty($list)){
		echo "get albums list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i<$max; $i++) {
    $id = $list[$i]['_id'];
    if(!isset($list[$i]['product_count'])){
      $product_count = $album_shop_model->count(array('dadid'=>$id));
      $ok = $album_model->update_set($id, array('product_count'=>(int)$product_count));
      if($ok){
        echo "success album_id: $id count: $product_count ok.\n";
	      $total++;      
      }
    }

	}
	if($max < $size){
		echo "Albums list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page album updated---------\n";
}
echo "total $total album rows updated.\n";

echo "All albums works done.\n";
?>
