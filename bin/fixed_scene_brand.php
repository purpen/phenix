#!/usr/bin/env php
<?php
/**
 * 修复情境品牌
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

echo "Prepare to fix brand province...\n";
$scene_brand_model = new Sher_Core_Model_SceneBrands();
$scene_product_model = new Sher_Core_Model_SceneProduct();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id','kind'),'page'=>$page,'size'=>$size);
	$list = $scene_brand_model->find($query, $options);
	if(empty($list)){
		echo "get brands list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i < $max; $i++) {
        $brand_id = (string)$list[$i]['_id'];
        //$product_count = $scene_product_model->count(array('brand_id'=>$brand_id));
        if(!isset($list[$i]['kind'])){
            $scene_brand_model->update_set($brand_id, array('kind'=>1));
            echo "fix brand [".$list[$i]['_id']."]..........\n";
            $total++;       
        }

	}
	if($max < $size){
		echo "brand list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total brand rows updated.\n";

echo "All brand fix done.\n";

