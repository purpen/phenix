#!/usr/bin/env php
<?php
/**
 * 同步订单索引状态值 
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

echo "Prepare to sync order_index ...\n";

$order = new Sher_Core_Model_Orders();
$order_index = new Sher_Core_Model_OrdersIndex();
$page = 1;
$size = 1000;
$is_end = false;

$total = 0;

while(!$is_end){
	$query = array();
	$options = array('field'=>array('_id','rid','status'), 'page'=>$page, 'size'=>$size);
	$list = $order->find($query, $options);
	if(empty($list)){
		echo "get orders list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    	$order_id = (string)$list[$i]['_id'];
		$status = $list[$i]['status']; 
    	$rid = $list[$i]['rid'];

    	$data = $order_index->first(array('order_id'=>$order_id));
    	if(empty($data)){
      	    continue;
    	}
    	if($data['status'] == $status){
      	    continue;
    	}
    	$order_index_id = $data['_id'];
    	$ok = $order_index->update_set($order_index_id, array('status'=>$status));
    	if($ok){
 		  	echo "sync success order_index [".(string)$rid."]..........\n";
    	}
		
		$total++;
		
		unset($data);
	}
	if($max < $size){
		$is_end = true;
		echo "order list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "sync [$total] is OK! \n";

