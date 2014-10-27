#!/usr/bin/env php
<?php
/**
 * fix user name => nickname
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php.example';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "Prepare to add user...\n";

$total = 0;

$file = $cfg_app_project_root.'u1.csv';
if(!file_exists($file)){
	echo "File[$file] not exists!!!\n";
	exit;
}
$file = fopen($file, 'r'); 
// 每次读取CSV里面的一行内容
while($data = fgetcsv($file)){ 
	// 此为一个数组，要获得每一个数据，访问数组下标即可
	$nickname = $data[1];
	$account = $data[2];
	$username = $data[3];
	$mobile = $data[5];
	
	$password = Sher_Core_Helper_Util::rand_string(6);
	
	try{
		if(empty($account) || empty($nickname)){
			continue; // 直接跳过
		}
		
		$user = new Sher_Core_Model_User();
		// 跳过手机号用户
		if(Sher_Core_Helper_Util::is_mobile($account)){
			$account = $account.'@qq.com';
		}
		// 验证用户名是否重复
		if(!$user->_check_name($nickname)){
			continue; // 直接跳过
		}
		
		 //echo "Account: $account | Nickname: $nickname | Username: $username | Mobile: $mobile\n";
		 
		$data = array(
			'account'  => $account,
			'password' => sha1($password),
			'nickname' => $nickname,

			'state' => Sher_Core_Model_User::STATE_OK,
			'role_id'  => Sher_Core_Model_User::ROLE_USER,
		);
		
		$profile = $user->get_profile();
		$profile['realname'] = $username;
		$profile['phone'] = $mobile;
		
		$data['profile'] = $profile;
		
		$ok = $user->create($data);
		if($ok){
			$new_data = $user->get_data();
		
			echo "Create the user[".$new_data['_id']."] is ok!\n";
		}
		unset($user);
		
		$total++;
	}catch(Sher_Core_Model_Exception $e){
		echo "Create the user failed: ".$e->getMessage();
		
		continue;
	}
}

echo "total $total user added.\n";

?>