#!/usr/bin/env php
<?php
/**
 * fix topic try_id => (int)try_id
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
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "Prepare to fix topic try_id...\n";

$topic = new Sher_Core_Model_Topic();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field'=>array('_id','try_id'), 'page'=>$page, 'size'=>$size);
	$list = $topic->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		$try_id = isset($list[$i]['try_id']) ? $list[$i]['try_id'] : 0; 
		
		$new_topic = new Sher_Core_Model_Topic();
		$new_topic->update_set($list[$i]['_id'], array('try_id'=>(int)$try_id), true);
		echo "fix topic[".$list[$i]['_id']."] try_id => [".$try_id."]..........\n";
		
		$total++;
		
		unset($new_topic);
	}
	if($max < $size){
		echo "topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total topic rows updated.\n";

echo "All topic fix done.\n";
?>