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

// 针对某个用户合并账户需求

try{
  $user_id = 89907;
  $account = 13929578884;
  $change_account = 13929578885;
  $user_model = new Sher_Core_Model_User();
  $user = $user_model->find_by_id($user_id);
  if(empty($user)){
    echo '用户不存在!';
    return false;
  }
  if((int)$user['account'] == $account){
    echo '条件存在，执行更改操作';
    //$ok = $user_model->update_set($user_id, array('account'=>$change_account, 'nickname'=>$change_account, 'password' => sha1('123456')));
  } 
  
  if ($ok){
    echo "change user is ok!\n";
  }
  
}catch(Sher_Core_Model_Exception $e){
  echo "change user failed: ".$e->getMessage();
}


?>
