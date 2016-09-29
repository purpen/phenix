<?php
/**
 * 话题、产品、灵感、产品专题、情境自动点赞(用小号)
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
echo "===============ITEM AUTO LOVE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "begin item auto add love ...\n";
$sleep_time = 900;
$topic_model = new Sher_Core_Model_Topic();
$product_model = new Sher_Core_Model_Product();
$stuff_model = new Sher_Core_Model_Stuff();
$special_subject_model = new Sher_Core_Model_SpecialSubject();
$sight_model = new Sher_Core_Model_SceneSight();

// 获取点赞名单---取块内容
$items = Sher_Core_Util_View::load_block('auto_gen_love_count', 1);

if(empty($items)){
  echo "block item_ids is empty! \n";
  sleep($sleep_time);
  exit(0);
}
$item_arr = explode(';',$items);

foreach($item_arr as $k=>$v){
  if(empty($v)){
    continue;
  }
  $v_arr = explode('|', $v);
  if(empty($v_arr) || count($v_arr)<6){
    echo "block format is wrong!! \n";
    continue;
  }
  $item_id = (int)$v_arr[0];
  $type = (int)$v_arr[1];
  $switch = (int)$v_arr[2];
  $page = (int)$v_arr[3];
  $max_count = (int)$v_arr[4];
  $interval_time = (int)$v_arr[5];
  $mark = sprintf("user_list_0%d", $page);

  if($switch==0){
    echo "item_id: $item_id is close! next.... \n";
    continue; 
  }

  try{
    if($type==1){ // 话题
      $fav_type = 2;
      $obj = $topic_model->load($item_id);
    }elseif($type==2){  // 灵感
      $fav_type = 4;
      $obj = $stuff_model->load($item_id);   
    }elseif($type==3){  // 产品
      $fav_type = 1;
      $obj = $product_model->load($item_id);   
    }elseif($type==4){  // 产品专题
      $fav_type = 9;
      $obj = $special_subject_model->load($item_id);    
    }elseif($type==5){
        $fav_type = 12;
        $obj = $sight_model->load($item_id);
    }

    if(empty($obj)){
      echo "item: $item_id not exist model!\n";
      continue;
    }

    if(!isset($obj['love_count'])){
      echo "item: $item_id is not field love_count!\n";
      continue;
    }

    if($max_count < (int)$obj['love_count']){
      echo "item: $item_id is max love count!\n";
      continue;
    }

    $user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $page);
    if(empty($user_list_arr)){
      echo "user list is empty!\n";
      continue;
    }

    $user_index = array_rand($user_list_arr, 1);
    $user_id = (int)$user_list_arr[$user_index];
    if(empty($user_id)){
      echo "user is null! \n";
      sleep($sleep_time);
      exit(0);
    }

    // 开始点赞
    // 检测是否已提交过申请
    $fav_model = new Sher_Core_Model_Favorite();
    if ($fav_model->check_loved($user_id, $item_id, $fav_type)) {
      echo "user id: $user_id has loved! \n";
      // 删除块用户
      //Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
      continue;
    }

    $fav_info = array(
      'type' => $fav_type,
    );
    $ok = $fav_model->add_love($user_id, $item_id, $fav_info);
    if($ok){
      echo "item: $item_id, type: $fav_type love is success!!\n";
      // 删除块用户
      //Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
      sleep($interval_time);
    }else{
      echo "item love is faile! \n";
    }
      
  }catch(Sher_Core_Model_Exception $e){
    echo "find item_model failed: ".$e->getMessage();
    continue;
  }

} // for end

echo "===========================ITEM LOVE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

$hr = date('G');
if($hr >= 10 && $hr <= 23){
    $time = rand(60, 300);
}else{
    $time = rand(600, 900);
}
// sleep N minute
sleep($time);
exit(0);
