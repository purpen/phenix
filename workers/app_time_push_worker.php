<?php
/**
 * 商城app定时推送提醒
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
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============APP TIME PUSH WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "app time push worder ...\n";

// 1分钟执行一次
$sleep_time = 60;

try{
  $conf = Sher_Core_Util_View::load_block('app_snatched_index_conf', 1);
  if(empty($conf)){
    echo "数据不存在!\n";
    sleep($sleep_time);
    exit(0);
  }
  $arr = explode('|', $conf);
  if(!empty($arr) && count($arr)==3){
    $product_id = (int)$arr[0];
    $cover_url = $arr[1];
    $type = (int)$arr[2];
    $product_model = new Sher_Core_Model_Product();
    $product = $product_model->load($product_id);
    if(empty($product)){
      echo "产品不存在!\n";
      sleep($sleep_time);
      exit(0);
    }
    if(!isset($product['app_snatched']) || empty($product['app_snatched'])){
      echo "非抢购产品!\n";
      sleep($sleep_time);
      exit(0);
    }
    $begin_time = $product['app_snatched_time'];
    $end_time = $product['app_snatched_end_time'];
    $now_time = time();

    // 开始前10分钟开始执行提醒推送
    if($now_time>($begin_time-600) && $now_time<($begin_time-480)){
      echo "时间吻合，开始执行提醒推送操作.\n";
      $support_model = new Sher_Core_Model_Support();

      $page = 1;
      // 极光一次最多推送100条,所以设置99
      $size = 99;
      $is_end = false;
      $total = 0;
      $alert = sprintf("您预约抢购的产品[%s]马上就要开始了!", $product['title']);
      while(!$is_end){
        $query = array();
        $query['target_id'] = $product_id;
        $query['event'] = Sher_Core_Model_Support::EVENT_APP_ALERT;
        $query['pushed'] = 0;
        $options = array('field' => array('_id', 'target_id', 'user_id', 'event', 'pushed'),'page'=>$page,'size'=>$size);
        $list = $support_model->find($query, $options);
        if(empty($list)){
          echo "get support list is null,exit......\n";
          sleep($sleep_time);
          exit(0);
        }
        $max = count($list);
        $user_arr = array();
        for ($i=0; $i < $max; $i++) {
          $id = $list[$i]['_id'];
          $user_id = (string)$list[$i]['user_id'];
          array_push($user_arr, $user_id);
          $total++;
        }

        // 开始执行推送操作
        $options = array(
          // 最多延迟5分钟再推送一次
          'time_to_live' => 300,
          // "android", "ios", "winphone"
          'plat_form' => array('ios'),
          'alias' => $user_arr,
          'extras' => array('infoType'=>1, 'infoId'=>$product_id),
          'apns_production' => true,
        );
        $push_ok = Sher_Core_Util_JPush::push($alert, $options);

        // 推送成功，更新状态
        if($push_ok['success']){
          echo "推送成功，更新状态!\n";
          for ($i=0; $i < $max; $i++) {
            $id = (string)$list[$i]['_id'];
            $support_model->update_set($id, array('pushed'=>1, 'info'=>$push_ok['data']));
          }       
        }else{
          echo "推送失败!\n";
        }

        if($max < $size){
          break;
        }
        $page++;
        echo "page [$page] updated---------\n";
      }

    }else{
      echo "等待下一次合适时间!\n";
      sleep($sleep_time);
      exit(0); 
    }

  }else{
    echo "数据结构不正确!\n";
    sleep($sleep_time);
    exit(0);
  } 
    
}catch(Exception $e){
    echo "app time push failed: ".$e->getMessage();
    sleep($sleep_time);
    exit(0);
}

echo "===========================APP TIME PUSH WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 minute
sleep($sleep_time);
exit(0);
