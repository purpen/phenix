#!/usr/bin/env php
<?php
/**
 * 修复情景产品分类
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
@require 'autoload.php';
@require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "fixed scene product category_id fields ...\n";

$scene_product_model = new Sher_Core_Model_SceneProduct();
$product_model = new Sher_Core_Model_Product();
$asset_service = Sher_Core_Service_Asset::instance();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;

while(!$is_end){
	$query = array('attrbute'=>1, 'kind'=>1, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_product_model->find($query, $options);
	if(empty($list)){
		echo "get scene product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $item = $list[$i];

        //返回图片数据
        $assets = array();
        $asset_query = array('parent_id'=>$item['_id'], 'asset_type'=>121);
        $asset_options['page'] = 1;
        $asset_options['size'] = 5;
        $asset_options['sort_field'] = 'latest';

        $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

        if(!empty($asset_result['rows'])){
          foreach($asset_result['rows'] as $key=>$value){
            array_push($assets, (string)$value['_id']);
          }
        }

        $product = $product_model->load((int)$item['oid']);
        if(empty($product)){
            echo "is empty!.\n";
            continue;
        }
        for($j=0;$j<count($assets);$j++){
            $ok = true;
            //$ok = $asset_model->update_set($assets[$j], array('parent_id'=>$product['_id'], 'asset_type'=>12));
            if($ok){
                echo "update success! \n";
                $total++;
            }
        }

	}   // endfor
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

/*
function category_change($id){
    switch($id){
        case 146:
            $new_id = 32;
            break;
        case 147:
            $new_id = 76;
            break;
        case 148:
            $new_id = 34;
            break;
        case 151:
            $new_id = 31;
            break;
        case 152:
            $new_id = 81;
            break;
        case 150:
            $new_id = 78;
            break;
        case 149:
            $new_id = 30;
            break;
        case 173:
            $new_id = 33;
            break;
        case 153:
            $new_id = 82;
            break;
        case 236:
            $new_id = 79;
            break;
        default:
            $new_id = 0;

    }
    return $new_id;
}
 */

echo "create product count: $total is OK! \n";

