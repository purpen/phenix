#!/usr/bin/env php
<?php
/**
 * fix user name => nickname
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

// 批量发短信
function batch_send_message($msg, $users=array()){
  if(empty($msg) || empty($users)){
    echo "is empty! \n";
    return false;
  }
  echo "begin send message ...\n";
  $number = 100000;
  $digged = new Sher_Core_Model_DigList();
  foreach($users as $k=>$v){
    $msg = sprintf("尊敬的嘉宾您好，您的“中国智能硬件蛋年创新大会·深圳站“活动票号：%d，时间：2015年5月16日18:00；地点：深圳·花样年福年广场。更多详情：http://dwz.cn/JsnBw #该信息转发无效#", $number);
    // 开始发送
    $message = Sher_Core_Helper_Util::send_defined_mms($v, $msg);
    // 添加到统计列表
    $digged->add_item_custom($birdegg_sz_jb, array('phone'=>$v, 'number'=>$number));
    echo "send success: $v \n";
    $number++;
    sleep(1);
  }

}

$msg = '太火鸟';
$users = array(
  15001120509,13552722577,
);
batch_send_message($msg, $users);
