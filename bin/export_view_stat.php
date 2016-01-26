#!/usr/bin/env php
<?php
/**
 * 导出来源统计
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

echo "begin export view stat...\n";
$view_stat_model = new Sher_Core_Model_ViewStat();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/chubao.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('IP', '时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
  $query = array();
  $query['target_id'] = 1;
	$options = array('page'=>$page,'size'=>$size);
	$list = $view_stat_model->find($query, $options);
	if(empty($list)){
		echo "get view stat list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $view_stat = $list[$i];
    if($view_stat){
      $d = date('Y-m-d H:m:s', $view_stat['created_on']);
      $row = array($view_stat['ip'], $d);
      fputcsv($fp, $row);
		  $total++;
    }
	}
	if($max < $size){
		echo "view_stat list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total view_stat rows export over.\n";

echo "All view_stat expore done.\n";

