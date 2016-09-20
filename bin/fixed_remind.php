#!/usr/bin/env php
<?php
/**
 * fix remind add column
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

echo "Prepare to fix remind stage ...\n";
$remind_model = new Sher_Core_Model_Remind();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','from_to'),'page'=>$page,'size'=>$size);
	$list = $remind_model->find($query,$options);
	if(empty($list)){
		echo "get remind list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		
        $data = $list[$i];
        $id = (string)$data['_id'];
        if(isset($data['from_to'])) continue;
        $ok = true;
        //$ok = $remind_model->update_set($id, array('from_to'=>1));
        if($ok){
            echo "update ok $id .\n";
		    $total++;       
        }
		
	}
	
	if($max < $size){
		echo "remind list is end!!!!!!!!!,exit.\n";
		break;
	}
	
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total remind rows updated.\n";

