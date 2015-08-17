<?php
/**
 * 试用自动申请(用小号)
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
echo "===============TRY AUTO APPLY WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "begin try apply ...\n";

$try_model = new Sher_Core_Model_Try();

// 获取试用名单---取块内容
$try_items = Sher_Core_Util_View::load_block('try_auto_apply', 1);

if(empty($try_items)){
  echo "block try_ids is empty! \n";
  // sleep 10 minute
  sleep(600);
  exit(0);
}
$try_arr = explode(';',$try_items);

foreach($try_arr as $k=>$v){
  if(empty($v)){
    continue;
  }
  $v_arr = explode('|', $v);
  if(empty($v_arr) || count($v_arr)<4){
    echo "block format is wrong!! \n";
    continue;
  }
  $try_id = (int)$v_arr[0];
  $switch = (int)$v_arr[1];
  $page = (int)$v_arr[2];
  $max_count = (int)$v_arr[3];
  $mark = sprintf("user_list_0%d", $page);

  if($switch==0){
    echo "try_id: $try_id is close! next.... \n";
    continue; 
  }

  try{
    $try = $try_model->load($try_id);
    if(empty($try)){
      echo "try: $try_id not exist model!\n";
      continue;
    }

    if($try['step_stat'] != 1){
      echo "try: $try_id status is wrong!\n";
      continue;   
    }

    if($max_count < $try['apply_count']){
      echo "try: $try_id is max apply count!\n";
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
      continue;
    }

    // 开始申请
    // 检测是否已提交过申请
    $apply_model = new Sher_Core_Model_Apply();
    
    if(!$apply_model->check_reapply($user_id, $try_id)){
      echo "user id: $user_id is applied!\n";
      // 删除块用户
      Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
      continue;
    }

    $apply_data = array(
      'target_id' => $try_id,
      'user_id' => $user_id,
      'content' => '我要试用!',
      // 虚假申请
      'is_invented' => 1,
    );

    $ok = $apply_model->apply_and_save($apply_data);
    if($ok){
      echo "apply is success!!\n";
      // 删除块用户
      Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
    }else{
      echo "apply is faile! \n";
    }
      
  }catch(Sher_Core_Model_Exception $e){
    echo "find try_model failed: ".$e->getMessage();
    continue;
  }

} // for end

echo "===========================TRY AUTO APPLY WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

$hr = date('G');
if($hr >= 9 && $hr <= 23){
    $time = rand(60, 600);
}else{
    $time = rand(600, 900);
}
// sleep N minute
sleep($time);
exit(0);
