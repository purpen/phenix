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
$tag_model = new Sher_Core_Model_SceneTags();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;

while(!$is_end){
	$query = array('kind'=>1, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_product_model->find($query, $options);
	if(empty($list)){
		echo "get scene product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $item = $list[$i];
        $row = array();
        $row['png_asset_ids'] = $item['png_asset_ids'];

        $new_tag_arr = array();
        if(isset($item['category_tags']) && !empty($item['category_tags'])){
            for($j=0;$j<count($item['category_tags']);$j++){
                $tag = $tag_model->load((int)$item['category_tags'][$j]);
                if(empty($tag)) continue;
                array_push($new_tag_arr, $tag['title_cn']);
            }       
        }

        $product = $product_model->load((int)$item['oid']);
        if(empty($product)) continue;
        $tag_arr = array();
        if(isset($product['category_tags']) && !empty($product['category_tags'])){
            $tag_arr = $product['category_tags'];
        }
        if(!empty($new_tag_arr)){
            for($k=0;$k<count($new_tag_arr);$k++){
                if(!empty($new_tag_arr[$k])) array_push($tag_arr, $new_tag_arr[$k]);
            }
        }
        $tag_arr = array_keys(array_count_values($tag_arr));
        $row['category_tags'] = $tag_arr;
        $tag_arr_s = implode(',', $tag_arr);
        echo "category_tags: $tag_arr_s.\n";

        $tags = $product['tags'];
        $new_tags = $item['tags'];
        for($k=0;$k<count($new_tags);$k++){
            array_push($tags, $new_tags[$k]);
        }
        $tags = array_keys(array_count_values($tags));
        $row['tags'] = $tags;
        $tags_s = implode(',', $tags);
        echo "tags: $tags_s. \n";

        if(!empty($item['brand_id'])){
            $row['brand_id'] = $item['brand_id'];
        }

        $ok = true;
        //$ok = $product_model->update_set($product['_id'], $row);
        $new_id = 0;
        //$new_id = $product['_id'];
        if($ok){
          // 更新全文索引
          //Sher_Core_Helper_Search::record_update_to_dig($new_id, 3); 
            echo "update product success! $new_id \n";
            $total++;
        }else{
            echo "update fail!!";
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

