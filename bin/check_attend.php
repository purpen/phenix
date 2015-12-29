#!/usr/bin/env php
<?php
/**
 * 检察试用拉票数
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
ini_set('memory_limit','512M');

echo "check attend ...\n";

$model = new Sher_Core_Model_Attend();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = 0;
	$query = array();
  $query['created_on'] = array('$gt'=>$time);
	$options = array('field' => array('_id', 'created_on'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get attend list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
      $total++;
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "check attend count: $total is OK! \n";

