#!/usr/bin/env php
<?php
/**
 * 生成网站地图sitemap.xml --产品
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}

include $config_file;

define('DOGGY_VERSION', $cfg_doggy_version);
define('DOGGY_APP_ROOT', $cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH', $cfg_app_class_path);

require $cfg_doggy_bootstrap;
@require 'autoload.php';
@require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "gen sitemap topic xml ...\n";

$model = new Sher_Core_Model_Product();
$sitemap_model = new Sher_Core_Util_Sitemap(false);
$sitemap_model->page('product');
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
  $query['published'] = 1;
	$options = array('field' => array('_id', 'published', 'stage'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $stage = $list[$i]['stage'];
    $time = date('Y-m-d');
    $url = $model->gen_view_url(array('_id'=>$id, 'stage'=>$stage));
    $sitemap_model->url($url, $time, 'never', '1.0');
    $total++;
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] is generate..---------\n";
}

$sitemap_model->close();

echo "gen sitemap product num: $total is OK! \n";

