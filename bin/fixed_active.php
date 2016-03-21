#!/usr/bin/env php
<?php
/**
 * 修改活动
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

echo "Begin fix active...\n";


$active_id = 12034;

$active_model = new Sher_Core_Model_Active();
$active = $active_model->first($active_id);
if($active){
  //$ok = $active_model->update_set($active['_id'], array('topic_ids'=>array(111072)));
}

echo " ok.\n";

