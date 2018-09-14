#!/usr/bin/env php
<?php
/**
 * 导出产品图片
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
$product_model = new Sher_Core_Model_Product();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 500;
$fid = Doggy_Config::$vars['app.contest.qsyd4_category_id'];
$from_to = 8;
$is_end = false;
$total = 0;
//$fpath = '/home/tianxiaoyi/qsyd4_data';
$fpath = '/home/tianxiaoyi/product_data';
$csv_file = '/home/tianxiaoyi/product_name.csv';


$data = Sher_Core_Helper_Util::csvGetLines($csv_file, 1000);


for($i=0; $i<count($data);$i++) {
  $title = trim($data[$i][1]);

  $product_list = $product_model->find(array('title'=>$title));
  if(!$product_list) {
    echo "$title 不存在\n";
    continue;
  }
  echo "可处理的产品： $title .\n";

  for($j=0;$j<count($product_list);$j++) {
    $product = $product_list[$j];
    if ($j==1){
      $product_dir = $product['title'];
    }else{
      $product_dir = $product['title'].$j;
    }

    $assets = $asset_model->find(array('asset_type'=>array('$in'=>array(10, 11, 15)), 'parent_id'=>$product['_id']));
    $img_url = array();
    foreach($assets as $k=>$v){
        // 导出图片
        $k = $k+1;
        $file_url = Sher_Core_Helper_Url::asset_qiniu_view_url($v['filepath']);

        $dir = sprintf("%s/%s", $fpath, $product_dir);
        is_dir($dir) OR mkdir($dir, 0777, true);

        $file_name = sprintf("%s/%s", $dir, $v['filename']);
        if(file_exists($file_name)){
            echo "file: $file_name is exist! next...\n";
            continue;
        }
        $ok = Sher_Core_Util_Image::download_img($file_url, $file_name);
        echo $ok."\n";
    }
    
  }


}

