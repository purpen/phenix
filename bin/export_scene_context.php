#!/usr/bin/env php
<?php
/**
 * 导出情境语境
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

echo "begin export scene_context info...\n";
$scene_context_model = new Sher_Core_Model_SceneContext();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/scene_context.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('标题', '分类', '描述', '标签');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_context_model->find($query, $options);
	if(empty($list)){
		echo "get scene context list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $item = $list[$i];
    if($item['category_id'] == 156) {
      $category = '哲理句子';
    }elseif($item['category_id'] == 157){
      $category = '经典歌词';
    }elseif($item['category_id'] == 158){
      $category = '电影台词';   
    }elseif($item['category_id'] == 159){
      $category = '豆瓣';
    }elseif($item['category_id'] == 161){
      $category = '英文名句'; 
    }elseif($item['category_id'] == 162){
      $category = '名人名言'; 
    }else{
      $category = '';
    }

    $row = array($item['title'], $category, $item['des'], implode(',', $item['tags']));
    fputcsv($fp, $row);

    $total++;

	}
	if($max < $size){
		echo "scene context list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total scene_context rows export over.\n";

echo "All scene_context expore done.\n";

