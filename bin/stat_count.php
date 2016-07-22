#!/usr/bin/env php
<?php
/**
 * 统计社区总数量(评论、浏览、申请试用)
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

echo "check social count ...\n";

$model = new Sher_Core_Model_Topic();
$try_model = new Sher_Core_Model_Try();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
$comment_count = 0;
$view_count = 0;
$love_count = 0;
while(!$is_end){
  $time = 0;
	$query = array();
	$options = array('field' => array('_id', 'comment_count', 'love_count', 'view_count', 'created_on'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
        $comment_count += isset($list[$i]['comment_count']) ? (int)$list[$i]['comment_count'] : 0;
        $view_count += isset($list[$i]['view_count']) ? (int)$list[$i]['view_count'] : 0;
        $love_count += isset($list[$i]['love_count']) ? (int)$list[$i]['love_count'] : 0;
        $total++;
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "check topic count: $total view_count: $view_count comment_count: $comment_count love_count: $love_count is OK! \n";

