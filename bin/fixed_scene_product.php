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
$page = 1;
$size = 200;
$is_end = false;
$total = 0;

while(!$is_end){
	$query = array('attrbute'=>array('$ne'=>1), 'kind'=>1, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_product_model->find($query, $options);
	if(empty($list)){
		echo "get scene product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $item = $list[$i];
        $row = array(
            'title' => $item['title'],
            'short_title' => $item['short_title'],
            'cover_id' => $item['cover_id'],
            'banner_id' => isset($item['banner_id']) ? $item['banner_id'] : '',
            'user_id' => $item['user_id'],
            'brand_id' => $item['brand_id'],
            'tags' => $item['tags'],
            'png_asset_ids' => $item['png_asset_ids'],
            'sale_price' => $item['sale_price'],
            'market_price' => $item['market_price'],
            'view_count' => $item['view_count'],
            'category_id' => category_change($item['category_id']),
            'category_tags' => $item['category_tags'],
            'advantage' => $item['summary'],
            'published' => 1,
            'approved' => 1,
            'stage' => 16,
        );
        $ok = true;
        //$ok = $product_model->create($row);
        //$product = $product_model->get_data();
        $new_id = 0;
        //$new_id = $product['_id'];
        if($ok){
          // 更新全文索引
          //Sher_Core_Helper_Search::record_update_to_dig($new_id, 3); 
            echo "create product success! $new_id \n";
            $total++;
        }else{
            echo "create fail!!";
        }

	}   // endfor
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

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

echo "create product count: $total is OK! \n";

