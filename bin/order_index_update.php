#!/usr/bin/env php
<?php
/**
 * 订单索表order_id改为字符串格式
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

echo "Prepare to fix order_id ...\n";

$order_index = new Sher_Core_Model_OrdersIndex();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field'=>array('_id','order_id'), 'page'=>$page, 'size'=>$size);
	$list = $order_index->find($query, $options);
	if(empty($list)){
		echo "get orders_index list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $id = $list[$i]['_id'];
    $order_id = (string)$list[$i]['order_id'];
    $ok = $order_index->update_set($id, array('order_id'=>$order_id));
    if($ok){
 		  echo "is success order_index fix [".(string)$id."]..........\n";   
    }
		$total++;
	}
	if($max < $size){
		echo "order_index list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix order_index is OK! \n";
?>
