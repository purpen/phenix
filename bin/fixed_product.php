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
	$query = array();
	$options = array('field' => array('_id','stage','inventory','sale_count','presale_count','presale_goals'),'page'=>$page,'size'=>$size);
	$list = $product->find($query,$options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		$updated = array(
			'process_voted' => 0,
			'process_presaled' => 0,
			'process_saled' => 0,
			'snatched' => 0,
			'snatched_time' => 0,
			'presale_inventory' => 0,
			'presale_count' => 0,
			'presale_people' => 0,
		);
		
		if($list[$i]['stage'] == 1){
			$updated['process_voted'] = 1;
		}elseif($list[$i]['stage'] == 5){
			$updated['process_presaled'] = 1;
			$updated['presale_inventory'] = $list[$i]['inventory'];
			$updated['presale_count'] = $list[$i]['sale_count'];
			$updated['presale_people'] = $list[$i]['presale_count'];
			
			if(isset($list[$i]['presale_goals'])){
				$updated['presale_goals'] = (float)$list[$i]['presale_goals'];
			}
			
		}elseif($list[$i]['stage'] == 9){
			$updated['process_saled'] = 1;
		}
		
		
		
		$new_product = new Sher_Core_Model_Product();
		$new_product->update_set($list[$i]['_id'], $updated);
		
		echo "fix product [".$list[$i]['_id']."] ..........\n";
		
		$total++;
		
		unset($new_product);
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
?>