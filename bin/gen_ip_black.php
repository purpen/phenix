#!/usr/bin/env php
<?php
/**
 * 生成邀请码
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

echo "Begin gen ip black...\n";

$count = 10;
$model = new Sher_Core_Model_IpBlackList();
for($i=0;$i<$count;$i++){
    $rand = rand(100000, 999999);
    $row = array(
        'ip' => $rand,
        'kind' => 2,
    );
    $ok = true;
    //$ok = $model->create($row);
}

$list = $model->find(array('kind'=>2));
for($i=0;$i<count($list);$i++){
    //$model->remove((string)$list[$i]['_id']);
}

echo " ok.\n";

