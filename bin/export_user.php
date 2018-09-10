#!/usr/bin/env php
<?php
/**
 * 导出用户信息
 * @author tianshuai
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

echo "begin export user info...\n";
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
#$fp = fopen('/home/tianxiaoyi/users.csv', 'a');
$fp = fopen('/Users/tian/users.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('account', 'phone', 'email', 'password', 'kind', 'wx_union_id', 'wx_uid', 'qq_uid', 'wb_uid', 'status');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $user_model->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $user = $list[$i];
    if (!isset($user['account'])) {
      echo "account not exist! .\n";
      continue;
    }

    if (!isset($user['kind'])) {
      $user['kind'] = 0;
    }

    $phone = '';
    $isMobile = Sher_Core_Helper_Util::isMobile($user['account']);
    if ($isMobile) {
      $phone = $user['account'];
    }

    $status = $user['state'];
    if ($user['state'] == 2) {
      $status = 1;
    } elseif ($user['state'] == -1) {
      $status = 0;
    }


    $row = array($user['account'], $phone, '', $user['password'], $user['kind'], $user['wx_union_id'], $user['wx_open_id'], $user['qq_uid'], $user['sina_uid'], $status);
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

echo "All user expore done.\n";

