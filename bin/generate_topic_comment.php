#!/usr/bin/env php
<?php
/**
 * 生成话题评论
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

echo "topic add comment ...\n";

$comment_list = array(
'居然还有这么多形状',
'不说功能，外形还是很萌的',
'这么色！',
'不能更色！',
'还有更色的么！？',
'这么羞羞。',
'我就看看。',
'羞羞也能高大上',
'以后不能好好看奥特曼了',
'小编能不能做试用',
'这么萌真的好么',
'给你点colour see see',
'满屏的马赛克',
'3.8日好应景',
'羞萌羞萌的',
'解读下“喜新厌旧和闺蜜有礼”是什么活动？',
'会不会有试用？',
'试用需要交报告么？',
'交试用报告可以打马赛克么?',

);


$topic_id = 29;
$user_page = 1;
$total = 0;
$mark = sprintf("user_list_0%d", $user_page);
$user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $user_page);
if(empty($user_list_arr)){
  echo "user list is empty!\n";
  exit;
}

$topic_model = new Sher_Core_Model_Topic();
$comment_model = new Sher_Core_Model_Comment();

for($i=0;$i<count($comment_list);$i++){
  $content = $comment_list[$i];

  $user_index = array_rand($user_list_arr, 1);
  $user_id = (int)$user_list_arr[$user_index];

  $row = array();
  $row['user_id'] = $user_id;
  $row['content'] = $content;
  $row['target_id'] = (string)$topic_id;
  $row['type'] = 2;
  $row['from_site'] = 1;

  try{
    //$ok = $comment_model->create($row);
    $ok = true;
    if($ok){
      sleep(10);
      $total++;
      echo "comment content: ".$row['content']."\n";
    }else{
      echo "comment save error.\n";
    }
  }catch(Exception $e){
    echo "comment save is error: ".$e->getMessage()."\n";
  }

}

echo "Topic add comment count: $total is OK! \n";

