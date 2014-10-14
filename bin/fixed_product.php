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
	$options = array('field' => array('_id','presale_start_time','presale_finish_time','voted_start_time','voted_finish_time','snatched_time'),'page'=>$page,'size'=>$size);
	$list = $product->find($query,$options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		$updated = array();
		
		$data = $list[$i];
		
		// 转换为时间戳, +12小时
		if(isset($data['snatched_time']) && !empty($data['snatched_time'])){
			$updated['snatched_time'] = strtotime($data['snatched_time']) + 12*60*60;
		}
		// 预售开始时间，结束时间
		if(isset($data['presale_start_time'])){
			$updated['presale_start_time'] = strtotime($data['presale_start_time']);
		}
		if(isset($data['presale_finish_time']) && !empty($data['presale_finish_time'])){
			$updated['presale_finish_time'] = strtotime($data['presale_finish_time']) + 24*60*60 - 1;
		}
		// 投票开始时间，结束时间
		if(isset($data['voted_start_time'])){
			$updated['voted_start_time'] = strtotime($data['voted_start_time']);
		}
		if(isset($data['voted_finish_time'])  && !empty($data['voted_finish_time'])){
			$updated['voted_finish_time'] = strtotime($data['voted_finish_time']) + 24*60*60 - 1;
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