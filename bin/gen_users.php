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


// 批量创建账号
function batch_create_user(){
	echo "Create batch the users ...\n";

  $user = new Sher_Core_Model_User();
  $data = array();
  $init_account = 18800000000;
  for($i=0;$i<=10;$i++){
    $account = $init_account + $i;
    $city = '北京';
    $email = (string)$account.'@139.com';
    $data = array(
      'account'  => (string)$account,
      'password' => sha1('123456'),
      'nickname' => (string)$account,
      'state' => Sher_Core_Model_User::STATE_OK,
      'role_id'  => Sher_Core_Model_User::ROLE_USER,
      'quality' => 1,
      'sex' => 1,
      'city' => $city,
      'email' => $email,
      'kind' => 9,
    );
    $profile = $user->get_profile();
    $profile['phone'] = (string)$account;
    $profile['job'] = '设计师';
    $data['profile'] = $profile;

    try{
      $ok = $user->create($data);
      
      if ($ok){
        echo "Create the user[$account] is ok!...num: $i...\n";
      }else{
        echo "Create the user[$account] is fail!...num: $i...\n";
      }
      $new_data = $user->get_data();

    }catch(Sher_Core_Model_Exception $e){
      echo "Create the user[$account] failed: ".$e->getMessage();
    }

  }
}
batch_create_user();

?>
