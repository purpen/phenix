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

// 创建系统账户
function create_system_user(){
	echo "Create system user ...\n";
	try{
		$user = new Sher_Core_Model_User();
		$data = array(
			'account'  => 'admin@taihuoniao.com',
			'password' => sha1('thn2014'),
			'nickname' => '太火鸟',
		
			'state' => Sher_Core_Model_User::STATE_OK,
			'role_id'  => Sher_Core_Model_User::ROLE_ADMIN,
		);
		
		$ok = $user->create($data);
		
		if ($ok){
			echo "Create system user is ok!\n";
		}
		
	}catch(Sher_Core_Model_Exception $e){
		echo "Create system user failed: ".$e->getMessage();
	}
}

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
			echo "Create the user is ok!\n";
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
		
			echo "Add the invitation is ok!\n";
		}
	}catch(Sher_Core_Model_Exception $e){
		echo "Add the invitation failed: ".$e->getMessage();
	}
}

// 创建分类
function create_category(){
	$categories = array(
		array('title'=>'创意话题','name'=>'fever','domain'=>2),
		array('title'=>'灵感欣赏','name'=>'idea','domain'=>2),
		array('title'=>'活动','name'=>'event','domain'=>2),
		
		array('title'=>'健康','name'=>'health','domain'=>1),
		array('title'=>'电子','name'=>'electronic','domain'=>1),
		array('title'=>'厨房','name'=>'kitchen','domain'=>1),
		array('title'=>'家居','name'=>'home','domain'=>1),
		array('title'=>'娱乐','name'=>'entertainment','domain'=>1),
		array('title'=>'亲子','name'=>'parenting','domain'=>1),
		array('title'=>'旅行','name'=>'travel','domain'=>1),
	);
	
	try{
		$model = new Sher_Core_Model_Category();
		foreach ($categories as $cate){
			$model->create($cate);
		}
	}catch(Sher_Core_Model_Exception $e){
		echo "Create the category failed: ".$e->getMessage();
	}
	
	echo "Create the category is ok! \n";
}

echo "Install ... \n";

create_system_user();

// $user_id = create_init_user();

// add_invitation($user_id);

// create_category();

$pic_url = 'http://img30.360buyimg.com/popWaterMark/g4/M01/00/04/rBEGFlNwLkgIAAAAAAJnJ2VjT6MAABaNAFbrHEAAmc_167.jpg';
$img_data = @file_get_contents($pic_url);
var_dump(strlen($img_data));
Sher_Core_Jobs_Queue::fetcher_image($pic_url, array('target_id'=>1051300353));

echo "Install is OK! \n";
?>