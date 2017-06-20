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

echo "stuff add love ...\n";


$user_page = 2;
$mark = sprintf("user_list_0%d", $user_page);
$user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $user_page);
if(empty($user_list_arr)){
  echo "user list is empty!\n";
  exit;
}


$stuff_model = new Sher_Core_Model_Stuff();
$favorite_model = new Sher_Core_Model_Favorite();

$pid = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
$page = 1;
$size = 2000;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = 0;
	$query = array();
  $query['fid'] = $pid;
  $query['from_to'] = 7;
	$options = array('field' => array('_id', 'title', 'category_id', 'from_to', 'view_count', 'love_count', 'created_on'), 'sort'=>array('love_count'=>1), 'page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $love_count = $list[$i]['love_count'];
    $view_count = $list[$i]['view_count'];
    $title = $list[$i]['title'];

    // 增加浏览数
    //$view_rand = rand(10, 100);
    //$stuff_model->inc_counter('view_count', $view_rand, $id);

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
        'target_id' => (int)$id,
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
    echo "stuff loved OK! rand: $rand_num, title: $title, love_count: $love_count.\n";
    echo "-------End--------\n";
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "stuff add love count: $total is OK! \n";

