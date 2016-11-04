#!/usr/bin/env php
<?php
/**
 * fix Inventory stage => process_voted,process_presaled,process_saled
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

echo "Prepare to fix Inventory stage ...\n";
$model = new Sher_Core_Model_Inventory();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','number'),'page'=>$page,'size'=>$size);
	$list = $model->find($query,$options);
	if(empty($list)){
		echo "get Inventory list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		
        $data = $list[$i];
        $id = $data['_id'];
        $number = Sher_Core_Helper_Util::getNumber();

        $ok = true;
        //$ok = $model->update_set($id, array('number'=>$number));
        if($ok){
            echo "update ok $id .\n";
            $total++;
        }else{
            echo "update fail $id .\n";
        }
		
	}
	
	if($max < $size){
		echo "Inventory list is end!!!!!!!!!,exit.\n";
		break;
	}
	
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total Inventory rows updated.\n";

echo "All Inventory fix done.\n";

