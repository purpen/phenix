#!/usr/bin/env php
<?php
/**
 * 导出情境
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

echo "begin export qsyd2 match user info...\n";
$stuff_model = new Sher_Core_Model_SceneSight();
$user_model = new Sher_Core_Model_User();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/Users/tian/sight_love7.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('作品ID', '作品名称','原图链接', '赞数量', '用户ID', '昵称', '电话', '职业');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
while(!$is_end){
	$query = array('is_check'=>1, 'deleted'=>0, 'love_count'=>array('$gt'=>6));
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get sight list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $sight = $list[$i];
    $file_url = '';
    if(!empty($sight['cover_id'])){
        $asset = $asset_model->load($sight['cover_id']);
        if(!empty($asset)) $file_url = Sher_Core_Helper_Url::asset_qiniu_view_url($asset['filepath']);   
    }
    $user = $user_model->extend_load($sight['user_id']);
    if(empty($user)){
        continue;
    }

      $row = array($sight['_id'], $sight['title'], $file_url, $sight['love_count'], $user['_id'], $user['nickname'], $user['profile']['phone'], $user['profile']['job']);
      fputcsv($fp, $row);

		  $total++;

	}
	if($max < $size){
		echo "sight list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total sight rows export over.\n";

echo "All sight expore done.\n";

