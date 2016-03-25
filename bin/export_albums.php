#!/usr/bin/env php
<?php
/**
 * 导出专辑
 * @author caowei@taihuoniao.com
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

echo "begin export albums...\n";
$model = new Sher_Core_Model_Albums();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/albums.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('ID', '专辑名称', '所属描述', '封面图', 'banner图');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
	
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get albums list is null,exit......\n";
		break;
	}
	$max = count($list);
	//print_r($list);
	
	for ($i=0; $i < $max; $i++) {
		$albums = $list[$i];
		if($albums){
		  $row = array($albums['_id'], $albums['title'], $albums['des'], $albums['cover_id'], $albums['banner_id']);
		  echo "albums[title],albums['des'],albums['cover_id'],albums['banner_id'] ...\n";
		  fputcsv($fp, $row);
			  $total++;
		}
	}
	if($max < $size){
		echo "albums list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);

echo "total $total albums rows export over.\n";
echo "All albums expore done.\n";
