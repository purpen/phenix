#!/usr/bin/env php
<?php
/**
 * 合并及修改商品分类
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

echo "Prepare to merge product category...\n";


$product_model = new Sher_Core_Model_Product();
$category_model = new Sher_Core_Model_Category();

$old_category_id = 34;
$new_category_id = 33;

$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
  $query['category_id'] = $old_category_id;
	$options = array('page'=>$page,'size'=>$size);
	$list = $product_model->find($query, $options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    if(1==1){
      $ok = $product_model->update_set($id, array('category_id'=>$new_category_id));
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

echo "fix product category count: $total is OK! \n";


// 重算分类计数
$rows = $category_model->find(array('domain'=>Sher_Core_Util_Constant::TYPE_PRODUCT));
for($i=0;$i<count($rows);$i++){
    $row = $rows[$i];
    $total_count = $product_model->count(array('category_id' => $row['_id']));
    $category_model->update_set($row['_id'], array('total_count' => $total_count));
    
}

echo "All category remath ok.\n";


