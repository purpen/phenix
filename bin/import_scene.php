#!/usr/bin/env php
<?php
/**
 * 通过文件导入情景语境
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

echo "begin import scene...\n";
$model = new Sher_Core_Model_SceneContext();
$total = 0;

//获取文件的编码方式
$contents = file_get_contents('/Users/tian/a.txt');
//$encoding = mb_detect_encoding($contents, array('GB2312','GBK','UTF-16','UCS-2','UTF-8','BIG5','ASCII'));

if(!$contents){
  echo 'empty file!';
  exit;
}

$array = explode("\n", $contents);
//print_r($array);

foreach($array as $v){
  if(empty(trim($v))){
    continue;
  }
  $arr = explode("@@", $v);
  if(!empty($arr) && count($arr)==2){
    $title = trim($arr[0]);
    $desc = trim($arr[1]);
    echo "$title @@ $desc \n";
    echo "-----------\n";
    // 是否重复上传
    $has_one = $model->first(array('title'=>$title));
    if($has_one){
      continue;
    }
    $rows = array(
      'title' => $title,
      'des' => $desc,
      'user_id' => 0,
    );
    $ok = true;
    $ok = $model->create($rows);
    if($ok){
      $total += 1;
    }
    
  }
}


echo "All list: $total emport done.\n";

