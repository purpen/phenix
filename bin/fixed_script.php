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

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "Prepare to fix user name...\n";
$user = new Lgk_Core_Model_User();
$page = 1;
$size = 2000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','name','nickname'),'page'=>$page,'size'=>$size);
	$list = $user->find($query,$options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
		if( isset($list[$i]['nickname']) && !empty($list[$i]['nickname']) ) {
			echo "skip nickname [".$list[$i]['nickname']."].\n";
			continue;
		}
		if( isset($list[$i]['name']) && !empty($list[$i]['name']) ) {
			$member = new Lgk_Core_Model_User();
			$member->update_set($list[$i]['_id'], array('nickname'=>$list[$i]['name']));
			echo "fix user[".$list[$i]['_id']."] nickname => [".$list[$i]['name']."]..........\n";
			unset($member);
			$total++;
		}
	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total user rows updated.\n";

echo "All user fix done.\n";
?>