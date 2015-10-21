<?php
/**
 * 定时创建全文索引---迅搜
 * @author tianshuai
 */

$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
@require 'autoload.php';
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============INDEX XunSearch WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 取最后一次更新的索引时间
$digged = new Sher_Core_Model_DigList();
$key_id = Sher_Core_Util_Constant::DIG_XUN_SEARCH_LAST_TIME;
$last_created_on = $digged->load($key_id);
if(!empty($last_created_on) && !empty($last_created_on['items'])){
  $topic_last_created_on = (int)$last_created_on['items']['topic_last_created_on'];
  $stuff_last_created_on = (int)$last_created_on['items']['stuff_last_created_on'];
  $product_last_created_on = (int)$last_created_on['items']['product_last_created_on'];
}else{
  $topic_last_created_on = 0;
  $stuff_last_created_on = 0;
  $product_last_created_on = 0;
}
echo "Prepare to build topic xun_search fulltext index...\n";
$topic = new Sher_Core_Model_Topic();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('deleted'=>0, 'published'=>1, 'created_on'=>array('$gt'=>$topic_last_created_on));
  $options = array('sort'=>array('created_on'=>1), 'page'=>$page, 'size'=>$size);
  $last_created_on = 0;
  $fail_ids = array();
	$list = $topic->find($query, $options);
	if(empty($list)){
		echo "Get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $item = $list[$i];
    if ($item) {
      //获取封面图
      if($item['cover_id']){
        $cover_id = $item['cover_id'];
      }else{
        $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Topic');
        if(!empty($cover)){
          $cover_id = $cover['_id'];
        }else{
          $cover_id = '';
        }
      }
      //添加全文索引
      $xs_data = array(
        'pid' => 'topic_'.(string)$item['_id'],
        'kind' => 'Topic',
        'oid' => $item['_id'],
        'cid' => 1,
        'title' => $item['title'],
        'cover_id' => $cover_id,
        'content' => strip_tags(htmlspecialchars_decode($item['description'])),
        'user_id' => $item['user_id'],
        'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
        'created_on' => $item['created_on'],
        'updated_on' => $item['updated_on'],
      );
      
      $result = Sher_Core_Util_XunSearch::update($xs_data);
      if($result['success']){
        //取最后一个创建时间点
        $last_created_on = $item['created_on'];
        $total++;
      }else{
        //记录失败ids
        $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_FAIL_IDS, $item['_id']);  
      }

    }

	}
	if($max < $size){
    //记录时间点
    if(!empty($last_created_on)){

      $digged->update_set($key_id, array('items.topic_last_created_on'=>$last_created_on), true);
    }
    //初始化变量
    $last_created_on = 0;
    unset($item);
		echo "Topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page topic updated---------\n";
}
echo "Total $total topic rows updated.\n";

echo "-------------//////////////-------------\n";

echo "Prepare to build product xun_search fulltext index...\n";
$product = new Sher_Core_Model_Product();
$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('deleted'=>0, 'published'=>1, 'created_on'=>array('$gt'=>$product_last_created_on));
  $options = array('sort'=>array('created_on'=>1), 'page'=>$page, 'size'=>$size);
	$list = $product->find($query, $options);
	if(empty($list)){
		echo "Get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $item = $list[$i];
    if ($item) {
      if($item['stage']==12){
        $stage = 9;
      }else{
        $stage = $item['stage'];
      }
      //获取封面图
      if($item['cover_id']){
        $cover_id = $item['cover_id'];
      }else{
        $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Product');
        if(!empty($cover)){
          $cover_id = $cover['_id'];
        }else{
          $cover_id = '';
        }
      }
      //添加全文索引
      $xs_data = array(
        'pid' => 'product_'.(string)$item['_id'],
        'kind' => 'Product',
        'oid' => $item['_id'],
        'cid' => $stage,
        'title' => $item['title'],
        'cover_id' => $cover_id,
        'content' => strip_tags(htmlspecialchars_decode($item['content'])),
        'desc'  =>$item['advantage'],
        'user_id' => $item['user_id'],
        'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
        'created_on' => $item['created_on'],
        'updated_on' => $item['updated_on'],
      );
      
      $result = Sher_Core_Util_XunSearch::update($xs_data);
      if($result['success']){
        //取最后一个创建时间点
        $last_created_on = $item['created_on'];
        $total++;
      }else{
        //记录失败ids
        $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_FAIL_IDS, $item['_id']);  
      }

    }
	}
	if($max < $size){
    //记录时间点
    if(!empty($last_created_on)){
      $digged->update_set($key_id, array('items.product_last_created_on'=>$last_created_on));   
    }
    //初始化变量
    $last_created_on = 0;
    unset($item);
		echo "Product list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page product updated---------\n";
}
echo "Total $total product rows updated.\n";

echo "-------------//////////////-------------\n";

echo "Prepare to build stuff xun_search fulltext index...\n";
$stuff = new Sher_Core_Model_Stuff();
$page = 1;
$size = 100;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('deleted'=>0, 'published'=>1, 'created_on'=>array('$gt'=>$stuff_last_created_on));
  $options = array('sort'=>array('created_on'=>1), 'page'=>$page, 'size'=>$size);
	$list = $stuff->find($query, $options);
	if(empty($list)){
		echo "Get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $item = $list[$i];
    if ($item) {
      //获取封面图
      if($item['cover_id']){
        $cover_id = $item['cover_id'];
      }else{
        $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Stuff');
        if(!empty($cover)){
          $cover_id = $cover['_id'];
        }else{
          $cover_id = '';
        }
      }
      //添加全文索引
      $xs_data = array(
        'pid' => 'stuff_'.(string)$item['_id'],
        'kind' => 'Stuff',
        'oid' => $item['_id'],
        'cid' => isset($item['from_to'])?$item['from_to']:0,
        'title' => $item['title'],
        'cover_id' => $cover_id,
        'content' => strip_tags(htmlspecialchars_decode($item['description'])),
        'user_id' => $item['user_id'],
        'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
        'created_on' => $item['created_on'],
        'updated_on' => $item['updated_on'],
      );
      
      $result = Sher_Core_Util_XunSearch::update($xs_data);
      if($result['success']){
        //取最后一个创建时间点
        $last_created_on = $item['created_on'];
        $total++;
      }else{
        //记录失败ids
        $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_FAIL_IDS, $item['_id']);  
      }

    }
	}
	if($max < $size){
    //记录时间点
    if(!empty($last_created_on)){
      $digged->update_set($key_id, array('items.stuff_last_created_on'=>$last_created_on));   
    }
    //初始化变量
    $last_created_on = 0;
    unset($item);
		echo "Product list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page stuff updated---------\n";
}
echo "Total $total stuff rows updated.\n";


echo "All index works done.\n";
echo "===========================INDEX XunSearch WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 hours
sleep(300);
exit(0);
