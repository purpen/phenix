#!/usr/bin/env php
<?php
/**
 * 评论追加楼层任务
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


// 可传入条件
function stat_comment($type=0, $target_id=null){

  if(empty($type)){
    $type_arr = array(1,2,3,4,5,6);
  }else{
    $type_arr = array((int)$type);
  }

  foreach($type_arr as $v){

    $model = new Sher_Core_Model_Comment();
    $page = 1;
    $size = 200;
    $is_end = false;
    $total = 0;
    while(!$is_end){
      $query = array('fid'=>(int)$fid);
      echo "category_id: $fid\n";
      $options = array('field' => array('_id', 'love_count', 'invented_love_count'),'page'=>$page,'size'=>$size);
      $list = $model->find($query, $options);
      if(empty($list)){
        echo "get stuff list is null,exit......\n";
        break;
      }
      $max = count($list);
      for ($i=0; $i < $max; $i++) {
        $invented_love_count = $list[$i]['invented_love_count'] + $list[$i]['love_count'];
        $model->update_set($list[$i]['_id'], array('love_count'=>$invented_love_count, 'invented_love_count'=>0));
        echo "set stuff[".$list[$i]['_id']."]..........\n";
        $total++;
      }
      if($max < $size){
        echo "stuff list is end!!!!!!!!!,exit.\n";
        break;
      }
      $page++;
      echo "page [$page] updated---------\n";
    }

  } //end foreach type_arr



}
echo "set comment floor fields ...\n";





echo "fix order_index is OK! \n";

