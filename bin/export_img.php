#!/usr/bin/env php
<?php
/**
 * 导出大赛图片
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

echo "begin export img match img...\n";
$stuff_model = new Sher_Core_Model_Stuff();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 500;
$fid = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
$from_to = 7;
$is_end = false;
$total = 0;
//$fpath = '/home/tianxiaoyi/qsyd3_data';
$fpath = '/Users/tian/qsyd3_data';

while(!$is_end){

	$query = array('fid'=>$fid, 'from_to'=>$from_to, 'deleted'=>0);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get match list is null,exit......\n";
		break;
	}
	$max = count($list);
    for ($i=0; $i < $max; $i++) {
        $stuff = $list[$i];
        $assets = $asset_model->find(array('asset_type'=>70, 'parent_id'=>$stuff['_id']));
        $img_url = array();
        foreach($assets as $k=>$v){
            // 导出图片
            $k = $k+1;
            $file_url = Sher_Core_Helper_Url::asset_qiniu_view_url($v['filepath']);
            $file_name = sprintf("%s/%s-%d-%d.jpg", $fpath, $stuff['title'], $stuff['_id'], $k);
            if(file_exists($file_name)){
                echo "file: $file_name is exist! next...\n";
                continue;
            }
            $ok = Sher_Core_Util_Image::download_img($file_url, $file_name);
            echo $ok."\n";
        }
	    $total++;

	}   //endfor
	if($max < $size){
		echo "match list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total match img rows export over.\n";

echo "All match img expore done.\n";

