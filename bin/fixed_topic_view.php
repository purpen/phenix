#!/usr/bin/env php
<?php
/**
 * 合并话题分类
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
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "topic view add...\n";


$topic_model = new Sher_Core_Model_Topic();

$page = 1;
$size = 100;
$is_end = false;
$total = 0;


while(!$is_end){
  $query = array();
	$options = array('field'=>array('_id','status','view_count','true_view_count'), 'page'=>$page, 'size'=>$size);
	$list = $topic_model->find($query, $options);
	if(empty($list)){
		echo "Topic list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i<$max; $i++) {
    $item = $list[$i];
    $view_count = $item['view_count'];
    $rand = rand(100, 300);
    $new_view_count = $view_count + $rand;
    $ok = true;
    //$ok = $topic_model->update_set($item['_id'], array('view_count'=>$new_view_count));
    if ($ok){
      $total++;
    }
	}   // endfor
	if($max < $size){
		$is_end = true;
		echo "Topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "topic count: [$total] is OK! \n";

?>
