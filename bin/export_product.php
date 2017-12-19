#!/usr/bin/env php
<?php
/**
 * 产品导出
 * @author tianshuai
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

echo "begin export product info...\n";
$product_model = new Sher_Core_Model_Product();

$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('~/products.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('编号', '名称', '价格', '创建时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
while(!$is_end){
	$query = array('is_vop'=>0, 'published'=>1, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $product_model->find($query, $options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $product = $list[$i];
    $row = array($product['_id'], $product['title'], $product['sale_price'], date('Y-m-d', $product['created_on']));
    fputcsv($fp, $row);

    $total++;

	}
	if($max < $size){
		echo "product list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total product rows export over.\n";

echo "All product expore done.\n";

