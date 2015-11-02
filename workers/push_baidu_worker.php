<?php
/**
 * 定时把优质用户创建的内容主动推送到百度
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
echo "===============PUSH BAIDU WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 获取要更新的对象ID数组
$digged = new Sher_Core_Model_DigList();

//获取话题更新的ID数组
$topic_ids = $digged->load(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_TOPIC_IDS);
if(!empty($topic_ids) && !empty($topic_ids['items'])){
  echo "begin push baidu for topic...\n";
  $topic_mode = new Sher_Core_Model_Topic();
  $total = 0;
  $item = null;
  foreach($topic_ids['items'] as $k=>$v){

    $item = $topic_mode->extend_load((int)$v);
    if(empty($item)){
      continue;
    }
    if($item['deleted']==1){
      continue;
    }
    $view_url = $item['view_url'];
    $result = Sher_Core_Helper_Util::push_baidu_url(array($view_url), 1);
    if($result['success']){
      $r = json_decode($result['data'], true);
      if(isset($r['success']) && !empty($r['success'])){
        echo "ok $v\n";
        print_r($r);
        //删除Dig相应ID
        $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_TOPIC_IDS, $v);
        $total++;
      }else{
        echo "error $r[error]: $r[message]\n";
      }
    }else{
      echo "error $result[msg] \n";
    }

  }//endfor

  echo "success push baidu topic $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取商品更新的ID数组
$product_ids = $digged->load(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_PRODUCT_IDS);
if(!empty($product_ids) && !empty($product_ids['items'])){
  echo "begin push baidu for product...\n";
  $product_mode = new Sher_Core_Model_Product();
  $total = 0;
  $item = null;
  foreach($product_ids['items'] as $k=>$v){
    
    $item = $product_mode->extend_load((int)$v);
    if(empty($item)){
      continue;
    }
    if($item['deleted']==1){
      continue;
    }
    $view_url = $item['view_url'];
    $result = Sher_Core_Helper_Util::push_baidu_url(array($view_url), 3);
    if($result['success']){
      $r = json_decode($result['data'], true);
      if(isset($r['success']) && !empty($r['success'])){
        echo "ok $v\n";
        print_r($r);
        //删除Dig相应ID
        $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_PRODUCT_IDS, $v);
        $total++;
      }else{
        echo "error $r[error]: $r[message]\n";
      }
    }else{
      echo "error $result[msg] \n";
    }

  }//endfor

  echo "success push baidu product $total .\n";
  echo "-------------//////////////-------------\n";
}//endif

/**
//获取灵感更新的ID数组
$stuff_ids = $digged->load(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_STUFF_IDS);
if(!empty($stuff_ids) && !empty($stuff_ids['items'])){
  echo "begin push baidu for stuff...\n";
  $stuff_mode = new Sher_Core_Model_Stuff();
  $total = 0;
  $item = null;
  foreach($stuff_ids['items'] as $k=>$v){

    $item = $stuff_mode->extend_load((int)$v);
    if(empty($item)){
      continue;
    }
    if($item['deleted']==1){
      continue;
    }

    $view_url = $item['view_url'];
    $result = Sher_Core_Helper_Util::push_baidu_url(array($view_url), 2);
    if($result['success']){
      $r = json_decode($result['data'], true);
      if(isset($r['success']) && !empty($r['success'])){
        echo "ok $v\n";
        print_r($r);
        //删除Dig相应ID
        $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_PUSH_BAIDU_STUFF_IDS, $v);
        $total++;
      }else{
        echo "error $r[error]: $r[message]\n";
      }
    }else{
      echo "error $result[msg] \n";
    }

  }//endfor

  echo "success push baidu stuff $total .\n";
  echo "-------------//////////////-------------\n";
}//endif

**/

echo "All content push baidu works done.\n";
echo "===========================PUSH BAIDU WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 10 minute
sleep(600);
exit(0);
