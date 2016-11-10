#!/usr/bin/env php
<?php
/**
 * fix product stage => process_voted,process_presaled,process_saled
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

echo "Prepare to fix product stage ...\n";
$product = new Sher_Core_Model_Product();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('stage'=>9, 'deleted'=>0);
	$options = array('field' => array('_id','deleted', 'stage', 'category_id', 'category_ids'),'page'=>$page,'size'=>$size);
	$list = $product->find($query,$options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		
        $data = $list[$i];
        $id = $data['_id'];

        $ok = true;
        if(!empty($data['category_id'])){
            //$ok = $product->update_set($id, array('category_ids'=>array((int)$data['category_id'])));
        }else{
            $ok = false;
        }
        if($ok){
            echo "update ok $id .\n";
            $total++;
        }else{
            echo "update fail $id .\n";
        }
		
	}
	
	if($max < $size){
		echo "product list is end!!!!!!!!!,exit.\n";
		break;
	}
	
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total product rows updated.\n";

echo "All product fix done.\n";

