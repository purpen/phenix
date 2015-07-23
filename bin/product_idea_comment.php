#!/usr/bin/env php
<?php
/**
 *  商品灵感评论导入--第3步
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
$comment_model = new Sher_Core_Model_Comment();
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

    $comments = $comment_model->find(array('target_id'=>(string)$topic_id, 'type'=>Sher_Core_Model_Comment::TYPE_STUFF));
    if(!empty($comments)){
      foreach($comments as $k=>$v){
        unset($v['_id']);
        $v['type'] = Sher_Core_Model_Comment::TYPE_PRODUCT;
        $v['target_id'] = (string)$id;
        $v['sub_type'] = 1;
        $v['product_idea'] = 1;
        $ok = $comment_model->create($v);
        if($ok){
          $comment_id = $comment_model->id;
          echo "update product idea comment success! product_id: $id comment_id: $comment_id \n";
          $total++;      
        }else{
          echo "update product idea comment fail! product_id: $id \n";
          $fail_total++;         
        }
      } //endfor
    } // comments if

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

