<?php
/**
 * 统计
 * @author tianshuai
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

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============DATA STAT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";
echo "Time: ".date('Y-m-d H:i:s', time())."\n";
echo "Start to DATA STAT ...\n";

$topic_model = new Sher_Core_Model_Topic();
$comment_model = new Sher_Core_Model_Comment();

$page = 1;
$size = 100;
$is_end = false;
$total = 0;

$topic_view_count = 0;
$topic_true_view_count = 0;
$topic_love_count = 0;
$topic_favorite_count = 0;
$comments_count = 0;

$comments_count = $comment_model->count();

while(!$is_end){
  $query = array();
	$options = array('field'=>array('_id','status','view_count','love_count','true_view_count','favorite_count'), 'page'=>$page, 'size'=>$size);
	$list = $topic_model->find($query, $options);
	if(empty($list)){
		echo "Topic list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i<$max; $i++) {
    $item = $list[$i];
    $topic_view_count += $item['view_count'];
    if (isset($item['true_view_count'])) $topic_true_view_count += $item['true_view_count'];
    $topic_love_count += $item['love_count'];
    $topic_favorite_count += $item['favorite_count'];
    $total++;
	}   // endfor
	if($max < $size){
		$is_end = true;
		echo "Topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}


$row = [
  'topic_view_count' => $topic_view_count,
  'topic_true_view_count' => $topic_true_view_count,
  'topic_love_count' => $topic_love_count,
  'topic_favorite_count' => $topic_favorite_count,
  'comments_count' => $comments_count,
];
Sher_Core_Util_Tracker::update_counter($row);


echo "stat count: [$total] is OK! \n";

