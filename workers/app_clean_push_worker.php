<?php
/**
 * 商城app定时清除推送提醒数据
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
echo "===============APP CLEAN PUSH WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";


function begin_stat(){
  echo "app clean push worder ...\n";

  try{
    $support_model = new Sher_Core_Model_Support();
    $page = 1;
    $size = 100;
    $is_end = false;
    $total = 0;
    while(!$is_end){

      $query = array();
      $query['event'] = Sher_Core_Model_Support::EVENT_APP_ALERT;
      $query['pushed'] = 1;
      $options = array('field' => array('_id', 'user_id', 'event', 'pushed'),'page'=>$page,'size'=>$size);
      $list = $support_model->find($query, $options);
      if(empty($list)){
        break;
      }else{
        $max = count($list);
        for ($i=0; $i < $max; $i++) {
          $id = (string)$list[$i]['_id'];
          $support_model->remove($id);
          $total++;
        }

        if($max < $size){
          break;
        }
        $page++;
        echo "page [$page] updated---------\n";
      }
    }
    echo "total count: $total.\n";
  }catch(Exception $e){
      echo "app clean push failed: ".$e->getMessage();
  }
}

// 每天零晨1点以内，执行一次
$begin_time = strtotime(sprintf("%s 00:00:00", date('Y-m-d')));
$end_time = strtotime(sprintf("%s 01:00:00", date('Y-m-d')));
$now_time = time();
if($now_time>=$begin_time && $now_time<=$end_time){
  // 开始统计...
  begin_stat();
}

echo "===========================APP CLEAN PUSH WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 hour
sleep(3600);
exit(0);
