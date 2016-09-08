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

$topic_model = new Sher_Core_Model_Topic();
$asset_model = new Sher_Core_Model_Asset();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
    $query['attrbute'] = 3;
	$options = array('field' => array('_id', 'attrbute'),'page'=>$page,'size'=>$size);
	$list = $topic_model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $attrbute = $list[$i]['attrbute'];
        if($attrbute!=3) continue;
        $ok = true;
        //$ok = $topic_model->update_set($id, array('category_id'=>241, 'fid'=>237));
        if($ok){
            $total++;
            echo "update ok topic id: $id.\n";
        }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix topic desc add tag-inlink is OK! \n";

