#!/usr/bin/env php
<?php
/**
 * 导出话题 link
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

echo "begin export topic...\n";
$topic_model = new Sher_Core_Model_Topic();
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/topic_links.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('ID', '标题', '用户', '链接', '创建时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $topic_model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $topic = $list[$i];
    if(empty($topic) || empty($topic['description'])){
      continue;
    }
    $view_url = Sher_Core_Helper_Url::topic_view_url($topic['_id']);
    $user_id = $list[$i]['user_id'];
    $user = $user_model->load($user_id);
    if($user){
      $d = date('y-m-d', $topic['created_on']);
      $nickname = $user['nickname'];
      $row = array($topic['_id'], $topic['title'], $nickname, $view_url, $d);
      fputcsv($fp, $row);
		  $total++;
    }
	}
	if($max < $size){
		echo "topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total topic rows export over.\n";

echo "All topic expore done.\n";

