#!/usr/bin/env php
<?php
/**
 * 获取用户列表
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

echo "begin fetch users...\n";
$user_model = new Sher_Core_Model_User();
$user_ext_model = new Sher_Core_Model_UserExtState();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$user_arr = array();

// 输出Excel列名信息
while(!$is_end){

  $query['rank_id'] = array('$gt'=>2);
	$options = array('page'=>$page,'size'=>$size);
	$list = $user_ext_model->find($query, $options);
	if(empty($list)){
		echo "get user ext list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $user_id = $list[$i]['_id'];
    $user = $user_model->load($user_id);
    if(empty($user)){
      continue;
    }
    if($user['kind']==9){
      continue;
    }
    if(!isset($user['profile']['phone']) || empty($user['profile']['phone'])){
      continue;
    }
    $phone = $user['profile']['phone'];
    if(Sher_Core_Helper_Util::is_mobile($phone)){
      if(in_array($phone, $user_arr)){
        continue;
      }else{
        echo "phone: $phone\n";
        array_push($user_arr, $phone);
        $total++;
      }
    }

	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

$fp = fopen('/home/tianxiaoyi/user_rank_sort.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

for($i=0;$i<count($user_arr);$i++){
  fputcsv($fp, array($user_arr[$i]));
}

fclose($fp);
echo "total $total user phone export over.\n";

echo "All user phone export done.\n";

