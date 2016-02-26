#!/usr/bin/env php
<?php
/**
 * 测试
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

echo "--------Begin test-----------\n";


$user_arr = array();
$user_model = new Sher_Core_Model_User();

$page = 1;
$size = 10000;
$query = array('kind'=>9, 'state'=>Sher_Core_Model_User::STATE_OK);
$options = array('page'=>$page, 'size'=>$size, 'fields' => array('_id', 'kind', 'state'));

//$users = $user_model->find($query, $options);

foreach ($users as $k=>$v){
        array_push($user_arr, $v['_id']);
}
echo implode(',', $user_arr)."\n";




echo "---------End test---------------\n";

