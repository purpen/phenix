#!/usr/bin/env php
<?php
/**
 * 通过内部小号批量点赞或收藏
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

echo "set stuff invented_love_count fields ...\n";

$model = new Sher_Core_Model_Stuff();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $fid = Doggy_Config::$vars['app.birdegg.category_id'];
	$query = array('fid'=>(int)$fid);
  echo "category_id: $fid\n";
	$options = array('field' => array('_id', 'view_count'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $invented_love_count = rand(5,50);
    $view_count = $list[$i]['view_count'] + $invented_love_count + 100;
		$model->update_set($list[$i]['_id'], array('invented_love_count'=>$invented_love_count, 'view_count'=>$view_count));
		echo "set stuff[".$list[$i]['_id']."]..........\n";
		$total++;
	}
	if($max < $size){
		echo "stuff list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}



echo "fix order_index is OK! \n";
?>
