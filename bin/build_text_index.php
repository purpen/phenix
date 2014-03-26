#!/usr/bin/env php
<?php
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

$indexer = Sher_Core_Service_TextIndexer::instance();
echo "Prepare to build user fulltext index...\n";
$user = new Sher_Core_Model_User();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','state'),'page'=>$page,'size'=>$size);
	$list = $user->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
	    if ($list[$i]['state'] != Sher_Core_Model_User::STATE_OK) {
	        echo "remove index:".$list[$i]['_id']. '...';
	        $indexer->remove_target_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    else {
	        echo "update index:".$list[$i]['_id']. '...';
	        $indexer->build_user_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    $total++;
	}
	if($max < $size){
		echo "stuff list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page user updated---------\n";
}
echo "total $total user rows updated.\n";

echo "All index works done.\n";
?>
