#!/usr/bin/env php
<?php
/**
 * 删除某个用户的全部话题
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
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "Prepare to begin del topic ...\n";


$topic_model = new Sher_Core_Model_Topic();

$user_id = 965565;

$topics = $topic_model->find(array('user_id'=>$user_id));
$count = count($topics);
echo "topic count: $count";
foreach($topics as $k=>$v){
  $id = $v['_id'];
  //$topic_model->remove($id);
}


echo "All topics is remove ok.\n";

