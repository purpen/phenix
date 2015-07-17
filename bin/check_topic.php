#!/usr/bin/env php
<?php
/**
 * 验证话题描述是否存在
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

echo "set topic add inlink-tag ...\n";

$model = new Sher_Core_Model_Topic();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = time() - 15*24*60*60;
	$query = array();
  $query['created_on'] = array('$gt'=>$time);
	$options = array('field' => array('_id', 'asset_count'),'page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $asset_count = $list[$i]['asset_count'];
    $id = $list[$i]['_id'];
    if(empty($asset_count)){
      $new_asset_count = $asset_model->count(array('parent_id'=>$id, 'asset_type'=>55));
      if(!empty($new_asset_count)){
        //$model->update_set($id, array('asset_count'=>$new_asset_count));
        echo "desc is empty id: $id. \n";
        echo "new asset account $new_asset_count \n";
        $total++;
      }

    }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix topic desc add tag-inlink is OK! \n";

