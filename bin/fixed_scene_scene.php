#!/usr/bin/env php
<?php
/**
 * 修复地盘
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

echo "fixed scene scene ...\n";

$scene_scene_model = new Sher_Core_Model_SceneScene();
$scene_tags_model = new Sher_Core_Model_SceneTags();
$page = 1;
$size = 50;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_scene_model->find($query, $options);
	if(empty($list)){
		echo "get scene scene list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $new_tags = array();
        if(isset($list[$i]['tags'])){
            foreach($list[$i]['tags'] as $v){
                $tag_id = (int)$v;
                if($tag_id<=0){
                    array_push($new_tags, $v);
                    continue;
                }
                $scene_tag = $scene_tags_model->load($tag_id);
                if($scene_tag){
                    array_push($new_tags, $scene_tag['title_cn']);
                }
            }
            $ok = true;
            //$ok = $scene_scene_model->update_set($id, array('tags'=>$new_tags, 'old_tags'=>$list[$i]['tags']));
            if($ok){
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

echo "fix scene scene count: $total is OK! \n";

