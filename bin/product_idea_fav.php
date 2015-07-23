#!/usr/bin/env php
<?php
/**
 *  商品灵感喜欢收藏导入--第4步
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

echo "product idea asset export ...\n";

$stuff_model = new Sher_Core_Model_Stuff();
$product_model = new Sher_Core_Model_Product();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
$fail_total = 0;
$asset_fail_total = 0;
while(!$is_end){
	$query = array('stage'=>Sher_Core_Model_Product::STAGE_IDEA);
	$options = array('page'=>$page,'size'=>$size);
	$list = $product_model->find($query, $options);
	if(empty($list)){
		echo "get product idea list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $topic_id = $list[$i]['old_stuff_id'];
    $cover_id = $list[$i]['cover_id'];
    $new_asset = array();

    $stuff_assets = $asset_model->find(array('parent_id'=>$topic_id, 'asset_type'=>Sher_Core_Model_Asset::TYPE_STUFF));
    if(!empty($stuff_assets)){
      foreach($stuff_assets as $k=>$v){
        $old_cover_id = (string)$v['_id'];
        unset($v['_id']);
        $v['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
        $v['parent_id'] = $id;
        $v['product_idea'] = 1;
        $ok = $asset_model->create($v);
        if($ok){
          $new_asset_id = (string)$asset_model->id;
          if(empty($cover_id) || $cover_id==$old_cover_id){
            $cover_id = $new_asset_id;
          }
          array_push($new_asset, $new_asset_id);
          echo "update product asset success! product_id: $id asset_id: $new_asset_id \n";         
        }else{
          echo "update product asset fail! product_id: $id \n";
          $asset_fail_total++;         
        }
      } //endfor
    } // stuff_assets if

    $ok = $product_model->update_set($id, array('cover_id'=>$cover_id, 'asset'=>$new_asset));
    //$ok = true;
    if($ok){
 		  echo "product idea[".$id."]. gen asset is OK!.........\n";
		  $total++;   
    }else{
      echo "product idea gen asset is fail! id: $id \n";
      $fail_total++;
    }

	}
	if($max < $size){
		echo "product idea list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "product gen asset is OK! count:$total \n";
echo "product gen asset is Fail! count:$fail_total \n";
echo "asset gen is Fail! count:$asset_fail_total \n";

