#!/usr/bin/env php
<?php
/**
 * 同步评论数量并且统计并写入评论楼层
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


stat_comment_floor(2, 108750, true);
//stat_comment_floor(3, null, true);
//stat_comment_floor(4, null, true);
//stat_comment_floor(6, null, true);


// 可传入条件
function stat_comment_floor($type=1, $target_id=null, $stat_floor=false){
  switch($type){
    case 2:
      $target_model = new Sher_Core_Model_Topic();
      break;
    case 3:
      $target_model = new Sher_Core_Model_Try();
      break;
    case 4:
      $target_model = new Sher_Core_Model_Product();
      break;
    case 6:
      $target_model = new Sher_Core_Model_Stuff();
      break;
    default:
      return;
  }

  $model = new Sher_Core_Model_Comment();

  $page = 1;
  $size = 200;
  $is_end = false;
  $total = 0;
  while(!$is_end){
    $query = array();
    if($target_id){
      $query['_id'] = $target_id;
    }
    $options = array('page'=>$page,'size'=>$size);
    $list = $target_model->find($query, $options);
    if(empty($list)){
      echo "get taret type: $type list is null,exit......\n";
      break;
    }
    $max = count($list);
    for ($i=0; $i < $max; $i++) {
      $id = (string)$list[$i]['_id'];
      $comment_count = $model->count(array('target_id'=>$id, 'type'=>(int)$type));
      if($comment_count>0){
        $target_model->update_set((int)$id, array('comment_count'=>$comment_count));
        //echo "target type:$type id: $id comment_count: $comment_count \n";
        $total++;
      }
      // 初始化楼层数
      if($stat_floor && $comment_count>0){
        $comment_list = $model->find(array('target_id'=>$id, 'type'=>(int)$type), array('sort'=>array('created_on'=>1)));
        $j = 0;
        foreach($comment_list as $k=>$v){
          $j++;
          $model->update_set((string)$v['_id'], array('floor'=>$j));
        }
      }
    }
    if($max < $size){
      echo "target type: $type list is end!!!!!!!!!,exit.\n";
      break;
    }
    $page++;
    echo "page [$page] updated---------\n";
  }
  echo "set target_type: $type total: $total comment_count fields complate!!! ...\n";


}
echo "set comment floor fields ...\n";


