<?php
/**
 * 定时更新全文索引---迅搜
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
echo "===============INDEX XunSearch WORKER UPDATE WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 获取要更新的对象ID数组
$digged = new Sher_Core_Model_DigList();

//获取话题更新的ID数组
$topic_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS);
if(!empty($topic_ids) && !empty($topic_ids['items'])){
  echo "Prepare to update topic fulltext index...\n";
  $topic_mode = new Sher_Core_Model_Topic();
  $total = 0;
  $item = null;
  foreach($topic_ids['items'] as $k=>$v){
    $item = $topic_mode->load((int)$v);
    if(empty($item)){
      continue;
    }
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
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update topic id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_FAIL_IDS, $item['_id']);
      echo "fail update topic id: $v $result[msg]";
    }

  }//endfor

  echo "success update topic $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取商品更新的ID数组
$product_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS);
if(!empty($product_ids) && !empty($product_ids['items'])){
  echo "Prepare to update product fulltext index...\n";
  $product_mode = new Sher_Core_Model_Product();
  $total = 0;
  $item = null;
  foreach($product_ids['items'] as $k=>$v){
    $item = $product_mode->load((int)$v);
    if(empty($item)){
      continue;
    }
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
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update product id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_FAIL_IDS, $item['_id']);
      echo "fail update product id: $v $result[msg]";
    }

  }//endfor

  echo "success update product $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取灵感更新的ID数组
$stuff_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS);
if(!empty($stuff_ids) && !empty($stuff_ids['items'])){
  echo "Prepare to update stuff fulltext index...\n";
  $stuff_mode = new Sher_Core_Model_Stuff();
  $total = 0;
  $item = null;
  foreach($stuff_ids['items'] as $k=>$v){
    $item = $stuff_mode->load((int)$v);
    if(empty($item)){
      continue;
    }
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
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update stuff id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_FAIL_IDS, $item['_id']);
      echo "fail update stuff id: $v $result[msg]";
    }

  }//endfor

  echo "success update stuff $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


echo "All index update works done.\n";
echo "===========================INDEX XunSearch UPDATE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 hours
sleep(300);
exit(0);
