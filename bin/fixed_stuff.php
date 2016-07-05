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

echo "fixed stuff ...\n";

$stuff_model = new Sher_Core_Model_Stuff();
$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('from_to'=>6, 'published'=>1);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $view_count = $list[$i]['view_count'];
        if($view_count<50000){
            $rand = rand(3000, 5000);
            $ok = $stuff_model->inc_counter('view_count', $rand, $id);
            $total++;
        }       
    }
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "All stuff count: $total update ok.\n";
?>
