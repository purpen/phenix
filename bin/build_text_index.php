#!/usr/bin/env php
<?php
/**
 * 建立全文索引脚本
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

$indexer = Sher_Core_Service_TextIndexer::instance();

/*
echo "Prepare to build user fulltext index...\n";
$user = new Sher_Core_Model_User();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'state'), 'page'=>$page, 'size'=>$size);
	$list = $user->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
	    if ($list[$i]['state'] != Sher_Core_Model_User::STATE_OK) {
	        echo "remove index:".$list[$i]['_id']. '...';
	        $indexer->remove_target_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    else {
	        echo "update user index:".$list[$i]['_id']. '...';
	        $indexer->build_user_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    $total++;
	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page user updated---------\n";
}
echo "total $total user rows updated.\n";

echo "-------------//////////////-------------\n";
*/

echo "Prepare to build topic fulltext index...\n";
$topic = new Sher_Core_Model_Topic();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'deleted'), 'page'=>$page, 'size'=>$size);
	$list = $topic->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
	    if ($list[$i]['deleted'] == 1) {
	        echo "remove index:".$list[$i]['_id']. '...';
	        $indexer->remove_target_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    else {
	        echo "update topic index:".$list[$i]['_id']. '...';
	        $indexer->build_topic_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    $total++;
	}
	if($max < $size){
		echo "topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page topic updated---------\n";
}
echo "total $total topic rows updated.\n";

echo "-------------//////////////-------------\n";

echo "Prepare to build product fulltext index...\n";
$product = new Sher_Core_Model_Product();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'deleted', 'published', 'stage'), 'page'=>$page, 'size'=>$size);
	$list = $product->find($query, $options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
	    if ($list[$i]['published'] == 0) {
	        echo "remove index:".$list[$i]['_id']. '...';
	        $indexer->remove_target_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    else {
	        echo "update product index:".$list[$i]['_id']. '...';
	        $indexer->build_product_index($list[$i]['_id']);
	        echo "ok.\n";
	    }
	    $total++;
	}
	if($max < $size){
		echo "product list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page product updated---------\n";
}
echo "total $total product rows updated.\n";

echo "-------------//////////////-------------\n";

echo "Prepare to build stuff fulltext index...\n";
$stuff = new Sher_Core_Model_Stuff();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('field' => array('_id', 'deleted', 'published'), 'page'=>$page, 'size'=>$size);
	$list = $stuff->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
        echo "update stuff index:".$list[$i]['_id']. '...';
        $indexer->build_stuff_index($list[$i]['_id']);
        echo "ok.\n";
	    $total++;
	}
	if($max < $size){
		echo "product list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page $page stuff updated---------\n";
}
echo "total $total stuff rows updated.\n";

echo "All index works done.\n";
?>
