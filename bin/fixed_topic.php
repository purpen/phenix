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

echo "Prepare to fix topic asset count...\n";
$topic = new Sher_Core_Model_Topic();
$page = 1;
$size = 2000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id'),'page'=>$page,'size'=>$size);
	$list = $topic->find($query,$options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
		$asset = new Sher_Core_Model_Asset();
		// 重新附件数量计算
		$asset_count = $asset->count(array(
			'parent_id' => (int)$list[$i]['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_EDITOR_TOPIC,
		));
		
		$new_topic = new Sher_Core_Model_Topic();
		$new_topic->update_topic_asset_count((int)$list[$i]['_id'], $asset_count);
		echo "fix topic[".$list[$i]['_id']."] asset_count => [".$asset_count."]..........\n";
		
		$total++;
		
		unset($new_topic);
		unset($asset);
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