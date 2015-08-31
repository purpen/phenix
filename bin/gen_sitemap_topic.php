#!/usr/bin/env php
<?php
/**
 * 生成网站地图sitemap.xml --话题
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

$model = new Sher_Core_Model_Topic();
$sitemap_model = new Sher_Core_Util_Sitemap(false);
$sitemap_model->page('topic');
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
  $query['published'] = 1;
	$options = array('field' => array('_id', 'published'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $time = date('Y-m-d');
    $url = Sher_Core_Helper_Url::topic_view_url($id);
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

echo "gen sitemap topic num: $total is OK! \n";

