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
$product_model = new Sher_Core_Model_Product();
$inventory_model = new Sher_Core_Model_Inventory();

$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('stage'=>9, 'deleted'=>0);
	$options = array('field' => array('_id','deleted', 'stage', 'category_id', 'category_ids'),'page'=>$page,'size'=>$size);
	$list = $product_model->find($query,$options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		
        $data = $list[$i];
        $product_id = $data['_id'];

        // 更新佣金
        if(!isset($data['commision_percent']) || empty($data['commision_percent'])){
            //$ok = $product_model->update_set($product_id, array('commision_percent'=>0.1, 'is_commision'=>1));
        }

        $inventory = $inventory_model->first(array('product_id'=>$product_id));
        if(!empty($inventory)){
            continue;
        }

        echo "exec id: $product_id.\n";
        $row = array(
            'product_id' => $product_id,
            'mode' => '默认',
            'quantity' => $data['inventory'],
            'sold' => $data['sale_count'],
            'price' => $data['sale_price'],
            'stage' => Sher_Core_Model_Inventory::STAGE_SHOP,
        );

        $ok = true;
        //$ok = $inventory_model->create($row);
        if($ok){
            echo "update ok $product_id .\n";
            $total++;
        }else{
            echo "update fail $product_id .\n";
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

