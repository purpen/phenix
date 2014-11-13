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

$indexer = Sher_Core_Service_OrdersIndexer::instance();

echo "Prepare to build order fulltext index...\n";
$order = new Sher_Core_Model_Orders();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('rid'), 'page'=>$page, 'size'=>$size);
	$list = $order->find($query, $options);
	if(empty($list)){
		echo "get order list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
        echo "update order index:".$list[$i]['_id']. '...';
        $indexer->build_orders_index($list[$i]['rid']);
        echo "ok.\n";
		
	    $total++;
	}
	if($max < $size){
		echo "Order list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page order updated---------\n";
}
echo "total $total order rows updated.\n";

echo "All index works done.\n";
?>
