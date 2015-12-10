#!/usr/bin/env php
<?php
/**
 * 导出礼品券
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

echo "begin export gift...\n";
$gift_model = new Sher_Core_Model_Gift();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/gift_code.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('券码', '抵扣金额', '最低使用限额', '过期时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
  $reg = '^BjB.*';
  $query = array();
  $query['code'] = array('$regex'=>$reg);
	$options = array('page'=>$page,'size'=>$size);
	$list = $gift_model->find($query, $options);
	if(empty($list)){
		echo "get gift list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $gift = $list[$i];
    if($gift){
      $d = date('y-m-d', $gift['expired_at']);
      $row = array($gift['code'], $gift['amount'], $gift['min_cost'], $d);
      echo "gift[code] ...\n";
      fputcsv($fp, $row);
		  $total++;
    }
	}
	if($max < $size){
		echo "gift list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total gift rows export over.\n";

echo "All gift expore done.\n";

