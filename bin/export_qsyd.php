#!/usr/bin/env php
<?php
/**
 * 奇思甬动大赛导出用户信息
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
$stuff_model = new Sher_Core_Model_Stuff();
$user_model = new Sher_Core_Model_User();
$category_model = new Sher_Core_Model_Category();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/qsyd2_match.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('作品ID', '作品名称', '链接', '类别', '作品简介', '原图链接', '姓名', '电话', '职业', '发布时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
while(!$is_end){
	$query = array('from_to'=>8, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get qsyd4 match list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $stuff = $list[$i];
    $view_url = sprintf("%s/qsyd_view4?id=%d", Doggy_Config::$vars['app.url.contest'], $stuff['_id']);
    $category = $category_model->load($stuff['category_id']);
    $cate_name = isset($category['title'])?$category['title']:'--';
      $assets = $asset_model->find(array('asset_type'=>70, 'parent_id'=>$stuff['_id']));
      $img_url = array();
      foreach($assets as $v){
        $file_url = Sher_Core_Helper_Url::asset_qiniu_view_url($v['filepath']);
        array_push($img_url, $file_url);
      }
      $img_urls = implode('@@', $img_url);

      $attr_label = $stuff['attr']==1 ? '个人' : '团队'; 
      $created_at = date('y-m-d', $stuff['created_on']);

      $row = array($stuff['_id'], $stuff['title'], $view_url, $cate_name, $stuff['description'], $img_urls, $stuff['name'], $stuff['tel'], $stuff['position'], $created_at);
      fputcsv($fp, $row);

		  $total++;

	}
	if($max < $size){
		echo "qsyd4 match list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total qsyd2 match rows export over.\n";

echo "All match expore done.\n";

