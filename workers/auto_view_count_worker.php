<?php
/**
 * 自动增加浏览量
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
echo "===============AUTO VIEW_COUNT WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to auto add view_count...\n";

// sleep N minute
$hr = date('G');
if($hr >= 9 && $hr <= 23){
    $sleep_time = rand(600, 1800);
}else{
    $sleep_time = rand(3600, 7200);
}

function auto_do($type, $size=1000){
  $query = array();
  $page = 1;
  $size = (int)$size;
  $is_end = false;
  $total = 0;
  $model_str = '';

  switch($type){
    case 1:
      $model = new Sher_Core_Model_Topic();
      $query = array('published'=>1, 'deleted'=>0);
      $model_str = 'Topic';
      break;
    case 2:
      $model = new Sher_Core_Model_Product();
      $model_str = 'Product';
      break;
    case 3:
      $model = new Sher_Core_Model_Try();
      $model_str = 'Try';
      break;
    default:
      sleep($sleep_time);
      exit(0);

  }

  while(!$is_end){
    $options = array('field'=>array('_id','view_count','published','deleted'), 'page'=>$page, 'size'=>$size);
    $list = $model->find($query, $options);
    if(empty($list)){
      echo "$model_str list is null,exit......\n";
      break;
    }
    $max = count($list);
    for ($i=0; $i<$max; $i++) {
      $do_it = false;
      $id = (int)$list[$i]['_id'];
      $view_count = $list[$i]['view_count'];
      $inc = rand(10, 50);

      switch($type){
        case 1:
          if($view_count<2000){
            $model->increase_counter('view_count', $inc, $id);
            $do_it = true;
          }
          break;
        case 2:
          if($view_count<1000){
            $model->inc_counter('view_count', $inc, $id);
            $do_it = true;
          }
          break;
        case 3:
          if($view_count<2500){
            $model->increase_counter('view_count', $inc, $id);
            $do_it = true;
          }
          break;
        default:

      }

      if($do_it){
        $total++;
        echo "update success $model_str step_stat [".(string)$id."]..........\n";
      }
    }
    if($max < $size){
      $is_end = true;
      echo "$model_str ID: [".(string)$id."] is inc...\n";
      break;
    }
    $page++;
    echo "page [$page] updated---------\n";
  }
  echo "update $model_str view_count: [$total] is OK! \n";
}

// 开始执行
auto_do(1);

sleep($sleep_time);
exit(0);
