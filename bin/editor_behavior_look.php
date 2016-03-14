#!/usr/bin/env php
<?php
/**
 * fix user name => nickname
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

$type = 0;
$evt = 0;
$target_id = 0;
$pub_user_id = 0;
$do_user_id = 0;

$editor_behavior_stat_model = new Sher_Core_Model_EditorBehaviorStat();
$query = array();
$options = array();

if($type){
  $query['type'] = (int)$type;
}
if($evt){
  $query['evt'] = (int)$evt;
}
if($target_id){
  $query['target_id'] = $target_id;
}
if($pub_user_id){
  $query['pub_user_id'] = (int)$pub_user_id;
}
if($do_user_id){
  $query['do_user_id'] = (int)$do_user_id;
}

$result = $editor_behavior_stat_model->find($query, $options);
print_r($result);
