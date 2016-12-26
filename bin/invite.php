#!/usr/bin/env php
<?php
/**
 * 邀请表查询
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

// 针对某个用户合并账户需求

try{
  $user_id = 0;
  $model = new Sher_Core_Model_InviteRecord();
  $query = array('evt'=>1);
  $options = array();
  $count = $model->count($query);
  echo $count;
  
}catch(Sher_Core_Model_Exception $e){
  echo "invite failed: ".$e->getMessage();
}

