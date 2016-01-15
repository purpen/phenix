#!/usr/bin/env php
<?php
/**
 * fix province string => int
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

echo "Prepare to fix apply province...\n";
$apply = new Sher_Core_Model_Apply();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','content','content_count'),'page'=>$page,'size'=>$size);
	$list = $apply->find($query, $options);
	if(empty($list)){
		echo "get apply list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
		$model = new Sher_Core_Model_Apply();
    if(!isset($list[$i]['content_count'])){
		  $model->update_set($list[$i]['_id'], array('content_count'=>strlen($list[$i]['content'])));
      echo "fix apply[".$list[$i]['_id']."]..........\n";
      unset($model);
      $total++;
    }
	}
	if($max < $size){
		echo "apply list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total apply rows updated.\n";

echo "All apply fix done.\n";

