#!/usr/bin/env php
<?php
/**
 * fix topic try_id => (int)try_id
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

echo "Prepare to fix stuff...\n";

$stuff = new Sher_Core_Model_Stuff();
$total_count = $stuff->count();
$feature_count = $stuff->count(array('featured'=>1));

$dig = new Sher_Core_Model_DigList();
$ok = $dig->set(array('_id'=>Sher_Core_Util_Constant::STUFF_COUNTER), array('items'=>array('total_count'=>$total_count, 'feature_count'=>$feature_count)));

unset($stuff);
unset($dig);

echo "All stuff count fix done.\n";
?>