#!/usr/bin/env php
<?php
/**
 * 检察评论点赞数
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

echo "check comment ...\n";

$comment_model = new Sher_Core_Model_Comment();
$favorite_model = new Sher_Core_Model_Favorite();

$page = 1;
$size = 2000;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = 0;
  $query = array();
  // 极米话题
  $query['target_id'] = '109581';
  $query['type'] = 2;
	$options = array('field' => array('_id', 'target_id', 'type', 'love_count', 'created_on'),'page'=>$page,'size'=>$size);
	$list = $comment_model->find($query, $options);
	if(empty($list)){
		echo "get comment list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = (string)$list[$i]['_id'];
    $love_count = $list[$i]['love_count'];
    if(!empty($love_count)){
      $favorite_count = $favorite_model->count(array('target_id'=>$id, 'event'=>2, 'type'=>3));
      if($love_count != $favorite_count){
        echo "love count: $love_count \n";
        echo "true_love count: $favorite_count \n";
        echo "--------------------------\n";
      }
    }
    $total++;
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "check comment count: $total is OK! \n";

