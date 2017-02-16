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

// 

try{

    $model = new Sher_Core_Model_ZoneProductLink();
    $row = array(
        'scene_id' => 15,
        'product_id' => 1090600025,
        'status' => 1,
    );
    $ok = true;
    //$ok = $model->create($row);
    if($ok){
        echo "successs!\n";
    }else{
        echo "fail!\n";
    }

  
}catch(Sher_Core_Model_Exception $e){
  echo "failed: ".$e->getMessage();
}

