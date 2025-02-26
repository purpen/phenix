#!/usr/bin/env php
<?php
/**
 * 修复情景语境
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

echo "fixed scene context ...\n";

$scene_context_model = new Sher_Core_Model_SceneContext();
$scene_tags_model = new Sher_Core_Model_SceneTags();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array();
	$options = array('page'=>$page,'size'=>$size);
	$list = $scene_context_model->find($query, $options);
	if(empty($list)){
		echo "get scene context list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = (string)$list[$i]['_id'];
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
            //$ok = $scene_context_model->update_set($id, array('tags'=>$new_tags, 'old_tags'=>$list[$i]['tags']));
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

echo "fix scene context count: $total is OK! \n";

