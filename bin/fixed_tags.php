#!/usr/bin/env php
<?php
/**
 * 修复标签
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

echo "fixed tags ...\n";

$tags_model = new Sher_Core_Model_Tags();
$scene_tags_model = new Sher_Core_Model_SceneTags();
$page = 1;
$size = 50;
$is_end = false;
$total = 0;
$parent_id = 0;
$fid = 0;
while(!$is_end){
	$query = array('parent_id'=>$parent_id, 'type'=>1);
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_tags_model->find($query, $options);
	if(empty($list)){
		echo "get scene tags list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $tag = $list[$i]['title_cn'];
        if(empty($tag)){
            continue;
        }
        $row = array(
            'name' => $tag,
            'fid' => $fid,
            'kind' => 1,
        );
        try{
            $ok = true;
            //$ok = $tags_model->create($row);
            if($ok){
                $total++;
                echo "success tag: $tag.\n";
            }
            sleep(0.2);
        }catch(Exception $e){
            echo "failed tag: $tag.\n";
            continue;
        }

	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "fix tags count: $total is OK! \n";

