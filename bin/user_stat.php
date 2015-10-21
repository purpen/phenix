#!/usr/bin/env php
<?php
/**
 * 用户注册时间统计
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

echo "begin stat user register...\n";

$date_arr = array(
  array('2014-01', '2014-01-01', '2014-01-31'),
  array('2014-02', '2014-02-01', '2014-02-28'),
  array('2014-03', '2014-03-01', '2014-03-31'),
  array('2014-04', '2014-04-01', '2014-04-30'),
  array('2014-05', '2014-05-01', '2014-05-31'),
  array('2014-06', '2014-06-01', '2014-06-30'),
  array('2014-07', '2014-07-01', '2014-07-31'),
  array('2014-08', '2014-08-01', '2014-08-31'),
  array('2014-09', '2014-09-01', '2014-09-30'),
  array('2014-10', '2014-10-01', '2014-10-31'),
  array('2014-11', '2014-11-01', '2014-11-30'),
  array('2014-12', '2014-12-01', '2014-12-31'),
  array('2015-01', '2015-01-01', '2015-01-31'),
  array('2015-02', '2015-02-01', '2015-02-28'),
  array('2015-03', '2015-03-01', '2015-03-31'),
  array('2015-04', '2015-04-01', '2015-04-30'),
  array('2015-05', '2015-05-01', '2015-05-31'),
  array('2015-06', '2015-06-01', '2015-06-30'),
  array('2015-07', '2015-07-01', '2015-07-31'),
  array('2015-08', '2015-08-01', '2015-08-31'),
  array('2015-09', '2015-09-01', '2015-09-30'),
  array('2015-10', '2015-10-01', '2015-10-31'),

);
$model_user = new Sher_Core_Model_User();

foreach($date_arr as $key=>$val){
  $query = array('created_on'=>array(
      '$gt' => strtotime($val[1]),
      '$lt' => strtotime($val[2]),
  ));
  $options = array();
  $user_count = $model_user->count($query, $options);
  echo "$val[0] user register count: $user_count.\n";
}

$user_count = $model_user->count();
echo "users total count: $user_count.\n";


