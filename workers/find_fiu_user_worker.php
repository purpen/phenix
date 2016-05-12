<?php
/**
 * 发现最Fiu伙伴－－根据场景创建数量
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
echo "===============FIND FIU USER WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 统计方法
function begin_stat(){
  echo "Begin to star...\n";
  $page=1;
  $size=100;
  $count = 0;
  $query = array();

  $options = array('field'=>array('_id','sight_count','state'), 'page'=>$page, 'size'=>$size, 'sort'=>array('sight_count'=>-1));
  $user_model = new Sher_Core_Model_User();
  $users = $user_model->find($query, $options);
  if(!empty($users)){
    $dig_model = new Sher_Core_Model_DigList();
    $dig_key_id = Sher_Core_Util_Constant::DIG_FIU_USER_IDS;
    for($i=0;$i<count($users);$i++){
      $dig_model->add_item_custom($dig_key_id, $users[$i]['_id']);
      $count++;
    }

  }
  echo "find user count: $count.\n";
  echo "End stat... \n";
}


// 每天零晨1点以内，统计一次
$begin_time = strtotime(sprintf("%s 00:00:00", date('Y-m-d')));
$end_time = strtotime(sprintf("%s 01:00:00", date('Y-m-d')));
$now_time = time();
if($now_time>=$begin_time && $now_time<=$end_time){
  // 开始统计...
  //begin_stat();
}

// 开始执行
begin_stat();

echo "-------------------------------------------------\n";
echo "===============FIND FIU USER WORKER WAKE DOWN===============\n";
echo "-------------------------------------------------\n";

// sleep 5 hour
sleep(3600*5);
exit(0);
