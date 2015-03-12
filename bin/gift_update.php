#!/usr/bin/env php
<?php
/**
 * 更新礼品券状态
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

echo "begin update gift ...\n";

$gift_model = new Sher_Core_Model_Gift();

$arr = array(
  array('Lkk55w2IwNRK5cd', 90369, 115020209706),
  array('Lkk55L2qRHJq3kL', 90370, 115020209705),
  array('Lkk557YgzIB1MJL', 90368, 115020209704),
  array('Lkk55HCxn5auixI', 10496, 115020209702),
  array('Lkk550XY0tP7AZp', 90365, 115020209700),
  array('Lkk55zdapABoPPB', 90363, 115020209696),
  array('Lkk55qZpAXawQhr', 80, 115020209694),
);
foreach($arr as $v){
  $options = array(
    'used_by' => $v[1],
    'used_at' => time(),
    'used' => 2,
    'order_rid' => $v[2],
  );
  $gift_model->update_set(array('code'=>$v[0]), $options);
  print_r($v);
}

echo "update gift is OK! \n";
?>
