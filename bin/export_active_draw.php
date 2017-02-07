#!/usr/bin/env php
<?php
/**
 * 导出活动抽奖记录
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

echo "begin export active draw info...\n";
$active_draw_model = new Sher_Core_Model_ActiveDrawRecord();
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$fp = fopen('/home/tianxiaoyi/draw.csv', 'a');
// Windows下使用BOM来标记文本文件的编码方式 
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// 输出Excel列名信息
$head = array('抽奖ID', '奖品','联系人', '电话', '地址', '邮编', '用户ID', '抽奖时间');
// 将数据通过fputcsv写到文件句柄
fputcsv($fp, $head);

while(!$is_end){
	$query = array('target_id'=>2, 'event'=>3, 'state'=>0, 'number_id'=>3);

	$options = array('page'=>$page,'size'=>$size);
	$list = $active_draw_model->find($query, $options);
	if(empty($list)){
		echo "get active draw list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i < $max; $i++) {
        $draw = $list[$i];

        $user_name = isset($draw['receipt']['name']) ? $draw['receipt']['name'] : '';
        $phone = isset($draw['receipt']['phone']) ? $draw['receipt']['phone'] : '';

        $province = isset($draw['receipt']['province']) ? $draw['receipt']['province'] : '';
        $district = isset($draw['receipt']['district']) ? $draw['receipt']['district'] : '';
        $address = isset($draw['receipt']['address']) ? $draw['receipt']['address'] : '';
        $address = sprintf("%s %s %s", $province, $district, $address);
        $zip = isset($draw['receipt']['zip']) ? $draw['receipt']['zip'] : '';

        $row = array((string)$draw['_id'], $draw['title'], $user_name, $phone, $address, $zip, $draw['user_id'], date('Y-m-d H:i:s', $draw['created_on']));
        print_r($row);
        fputcsv($fp, $row);

		$total++;
	}
	if($max < $size){
		echo "active draw list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
fclose($fp);
echo "total $total acitve draw rows export over.\n";

echo "All active draw expore done.\n";

