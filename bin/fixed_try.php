#!/usr/bin/env php
<?php
/**
 * 修复试用字段
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

echo "fixed try fields ...\n";

$try_model = new Sher_Core_Model_Try();
$topic_model = new Sher_Core_Model_Topic();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $try_model->find($query, $options);
	if(empty($list)){
		echo "get try list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    if(!isset($list[$i]['kind'])){
        $ok = true;
      //$ok = $try_model->update_set($id, array('kind'=>1));
      if($ok){
        $total++;
      }
    }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix try fields count: $total is OK! \n";

