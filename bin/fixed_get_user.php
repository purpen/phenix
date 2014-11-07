#!/usr/bin/env php
<?php
/**
 *fix user name => nickname
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

echo "Prepare to get user...\n";

try{
    $user = new Sher_Core_Model_User();
	$arr = array('nickname' =>'@@@@');
    $ok = $user->find($arr);
    foreach($ok as $brr){
    //echo $brr['account']."\t".$brr['_id']."\t".$brr['nickname']."\n";
    echo "<table border='1'>";
	echo "<tr>";
  	echo "<td>" . $brr['account'] . "</td>";
 	echo "<td>" . $brr['_id'] . "</td>";
  	echo "<td>" . $brr['nickname'] . "</td>";
	echo "</tr>";
	echo "</table>";

	//exit;
    }
    }catch(Sher_Core_Model_Exception $e){
	echo "Get the user failed: ".$e->getMessage();
		
	continue;

   }
?>


