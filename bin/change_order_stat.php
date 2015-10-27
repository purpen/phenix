#!/usr/bin/env php
<?php
/**
 * 手动修改某个订单的状态值，针对特殊用户做处理－－－－避免常用
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
require $cfg_app_rc;


echo "begin change order stat...\n";
//这个用户订单状态是由待发货－>已退款
//记录历史操作的rid: 115010409490,
$rid = '';
$user_id = 0;
$order_mode = new Sher_Core_Model_Orders();
$order = $order_mode->find_by_rid($rid);
if(empty($order)){
  echo "order is not found.\n";
  return false;
}
if($order['user_id'] != $user_id){
  echo "is not same user id.\n";
  return false;
}
//$ok = $order_mode->refunded_order((string)$order['_id'], array('refunded_price'=>0));
if($ok){
  echo "操作成功.\n";
}else{
  echo "操作失败.\n";
}

echo "Total $total order rows updated.\n";
?>
