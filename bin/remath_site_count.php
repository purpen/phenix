#!/usr/bin/env php
<?php
/**
 * Remath site data count
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

// 用户总数
function remath_user_count(){
	echo "Remath user count ...\n";
	$data = array();
	try{
		$user = new Sher_Core_Model_User();
		$data['users_count'] = $user->count();
		
		$data['active_users_count'] = $user->count(array('state'=>2));
	}catch(Sher_Core_Model_Exception $e){
		echo "Remath user failed: ".$e->getMessage();
	}
	return $data;
}

// 产品总数
function remath_product_count(){
	echo "Remath user count ...\n";
	$data = array();
	try{
		$product = new Sher_Core_Model_Product();
		$data['products_count'] = $product->count();
		
		$data['vote_products_count'] = $product->count(array('stage'=>1));
		$data['onsale_products_count'] = $product->count(array('stage'=>9));
	}catch(Sher_Core_Model_Exception $e){
		echo "Remath product failed: ".$e->getMessage();
	}
	return $data;
}

// 话题总数
function remath_topic_count(){
	echo "Remath topic count ...\n";
	$data = array();
	try{
		$topic = new Sher_Core_Model_Topic();
		$data['topics_count'] = $topic->count();
	}catch(Sher_Core_Model_Exception $e){
		echo "Remath topic failed: ".$e->getMessage();
	}
	return $data;
}

// 订单总数
function remath_order_count(){
	echo "Remath order count ...\n";
	$data = array();
	try{
		$orders = new Sher_Core_Model_Orders();
		$data['orders_count'] = $orders->count();
		
		$data['success_orders_count'] = $orders->count(array(
			'status' => array('$gt' => Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT)
		));
		
	}catch(Sher_Core_Model_Exception $e){
		echo "Remath order failed: ".$e->getMessage();
	}
	
	return $data;
}

echo "Start to remath ... \n";

$site_count = array();
$user_data = remath_user_count();
$product_data = remath_product_count();
$topic_data = remath_topic_count();
$order_data = remath_order_count();

$site_count = array_merge($user_data, $product_data, $topic_data, $order_data);

echo "Update to site data... \n";
$tracker = new Sher_Core_Model_Tracker();
$ok = $tracker->remath_sitedata_counter('frbird', $site_count);
if($ok){
	echo "Remath update OK! \n";
}

echo "Remath is OK! \n";
?>