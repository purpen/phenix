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
@require $cfg_app_rc;

set_time_limit(0);

// 创建初始账号
function create_init_user(){
	echo "Create the user ...\n";
	try{
		$user = new Sher_Core_Model_User();
		$data = array(
			'account'  => 'purpen.w@gmail.com',
			'password' => sha1('thn2014'),
			'nickname' => 'purpen',
		
			'state' => Sher_Core_Model_User::STATE_OK,
			'role_id'  => Sher_Core_Model_User::ROLE_USER,
		);
		
		$ok = $user->create($data);
		
		if ($ok){
			echo "Create the user is Ok!\n";
		}
	
		$new_data = $user->get_data();
		
	}catch(Sher_Core_Model_Exception $e){
		echo "Create the user failed: ".$e->getMessage();
	}
	
	return $new_data['_id'];
}

// 添加邀请码
function add_invitation($user_id){
	try{
		if ($user_id){
			$invitation = new Sher_Core_Model_Invitation();
			$invitation->generate_for_user($user_id, 5);
		
			echo "Add the invitation is Ok!\n";
		}
	}catch(Sher_Core_Model_Exception $e){
		echo "Add the invitation failed: ".$e->getMessage();
	}
}

echo "Install ... \n";

$user_id = create_init_user();

add_invitation($user_id);

echo "Install is OK! \n";
?>