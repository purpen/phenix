#!/usr/bin/env php
<?php
/**
 * 灵感自动投票
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

echo "stuff special add love ...\n";


$user_page = 1;
$mark = sprintf("user_list_0%d", $user_page);
$user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $user_page);
if(empty($user_list_arr)){
  echo "user list is empty!\n";
  exit;
}

$stuff_model = new Sher_Core_Model_Stuff();
$favorite_model = new Sher_Core_Model_Favorite();

$stuff_ids = array(112570,112581,112572,112573,112576,112484,112578,112416,112437);
$total = 0;

for($i=0;$i<count($stuff_ids);$i++){
  $id = $stuff_ids[$i];
  // 随机点赞次数
  $rand_num = rand(1, 8);
  echo "-----Begin------\n";
  for($j=0;$j<=$rand_num;$j++){
    $user_index = array_rand($user_list_arr, 1);
    $user_id = (int)$user_list_arr[$user_index];
    if(empty($user_id)){
      echo "user_id is null! \n";
      continue;
    }

    $row = array(
      'type'  => 4,
      'target_id' => $id,
      'event' => 2,
      'user_id' => $user_id,
    );

    if(!$favorite_model->check_loved($user_id, $id, 4)){
      //$ok = $favorite_model->create($row);
      $ok = true;
      if($ok){
        $total++;
        sleep(1);
        // 删除块用户
        Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
        echo "is save OK!\n";
      }else{
        echo "stuff loved fail!!!\n";
      }
    }     
  } // endfor rand_num
  echo "stuff loved OK! rand: $rand_num, stuff_id: $id.\n";
  echo "-------End--------\n";

} // endfor

echo "stuff add love count: $total is OK! \n";

