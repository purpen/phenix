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

echo "Prepare to build order express...\n";

$filepath = Doggy_Config::$vars['app.storage.tmpdir'];
$filename = 'frbird_report_20141228.csv';

$data_file = $filepath.'/'.$filename;
// 检测是否已经存在该文件
if(!is_file($data_file)){
	echo "Order File is not exist: ".$data_file;
	exit;
}
$total = 0;
$fp = fopen($data_file, 'r');
while($data = fgetcsv($fp)){
	$rid = trim($data[0]);
	$express_no = trim($data[8]);
	
	$express_title = '申通快递';
	$express_caty = 's';
	
	echo "Order[$rid],express[$express_caty][$express_title][$express_no]\n";

  if(empty($express_no)){
    echo "Order[$rid], express is empty";
    continue;
  }
	
	$model = new Sher_Core_Model_Orders();
	$order_info = $model->find_by_rid($rid);
	if(empty($order_info)){
		echo "Order[$rid],not exist!\n";
		continue;
	}
	$id = (string)$order_info['_id'];
	
	// 仅已付款订单，可发货
	if($order_info['is_payed'] != 1 || $order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
		echo "Order[$rid],no pay or status is wrong!\n";
		continue;
	}
	
	// 更新物流信息
	$ok = $model->sended_order($id, array('express_caty'=>$express_caty, 'express_no'=>$express_no));
	if(!$ok){
		echo "Order[$rid],update failed!\n";
		continue;
	}
	
	$total++;
}
fclose($fp);


echo "Total $total order rows updated.\n";
?>
