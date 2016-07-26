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

$product_model = new Sher_Core_Model_Product();
$fav_model = new Sher_Core_Model_Favorite();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
$fail_total = 0;
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

    $stuff_favs = $fav_model->find(array('type'=>Sher_Core_Model_Favorite::TYPE_STUFF, 'target_id'=>(int)$topic_id));
    if(!empty($stuff_favs)){
      foreach($stuff_favs as $k=>$v){
        unset($v['_id']);
        $v['type'] = Sher_Core_Model_Favorite::TYPE_PRODUCT;
        $v['target_id'] = $id;
        $v['product_idea'] = 1;
        $ok = $fav_model->create($v);
        if($ok){
          $total++;
        }else{
          $fail_total++;
        }
      } //endfor
    } // stuff_assets if

	}
	if($max < $size){
		echo "product idea list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "product gen fav record is OK! count:$total \n";
echo "product gen fav record is Fail! count:$fail_total \n";

