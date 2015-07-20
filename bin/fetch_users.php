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
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/user_list.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('ID', '账户', '昵称', '创建时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
	$query = array('kind'=>9);
	$options = array('page'=>$page,'size'=>$size);
	$list = $user_model->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $user = $list[$i];
    if(empty($user)){
      continue;
    }
    $d = date('y-m-d', $user['created_on']);
    $nickname = $user['nickname'];
    $row = array($user['_id'], $user['account'], $nickname, $d);
    fputcsv($fp, $row);
    $total++;
	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total user rows export over.\n";

echo "All user export done.\n";

