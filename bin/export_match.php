#!/usr/bin/env php
<?php
/**
 * 大赛导出用户信息
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

echo "begin export match user info...\n";
$stuff_model = new Sher_Core_Model_Stuff();
$user_model = new Sher_Core_Model_User();
$category_model = new Sher_Core_Model_Category();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/Users/tian/qsyd_match3.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('ID', '项目名称', '项目类别', '类型', '公司/团队名称', '参赛者姓名', '联系人姓名', '职位', '电话', '邮箱', '地址', '作品链接', '原图链接');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);
$fid = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
$from_to = 7;
while(!$is_end){
	$query = array('fid'=>$fid, 'from_to'=>$from_to);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get match list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $stuff = $list[$i];
    $view_url = sprintf(Doggy_Config::$vars['app.url.contest']."/qsyd_view/%s.html", $stuff['_id']);
    $user_id = $list[$i]['user_id'];
    $user = $user_model->load($user_id);
    $category = $category_model->load($stuff['category_id']);
    $cate_name = isset($category['title'])?$category['title']:'--';
    if($user){
      $assets = $asset_model->find(array('asset_type'=>70, 'parent_id'=>$stuff['_id']));
      $img_url = array();
      foreach($assets as $v){
        $file_url = Sher_Core_Helper_Url::asset_qiniu_view_url($v['filepath']);
        array_push($img_url, $file_url);
      }
      $img_urls = implode('|', $img_url);

      $realname = isset($user['profile']['realname'])?$user['profile']['realname']:'--';
      $phone = isset($user['profile']['phone'])?$user['profile']['phone']:'--';
      $job = isset($user['profile']['job'])?$user['profile']['job']:'--';
      $address = isset($user['profile']['address'])?$user['profile']['address']:'--';

      if($stuff['attr'] == 2) {
        $attr = '团队';
      }else{
        $attr = '个人';
      }

      $row = array($stuff['_id'], $stuff['title'], $cate_name, $attr, $stuff['company'], $stuff['name'], $stuff['c_name'], $stuff['position'], $stuff['tel'], $stuff['email'], $stuff['address'], $view_url, $img_urls);
      fputcsv($fp, $row);

		  $total++;
    }

	}
	if($max < $size){
		echo "match list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total match rows export over.\n";

echo "All match expore done.\n";

