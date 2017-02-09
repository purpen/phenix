#!/usr/bin/env php
<?php
/**
 * 导入中奖者快递单号
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


$fp = fopen("/home/tianxiaoyi/import_draw.csv", "r"); 
if($fp){ 
    echo "file open success~! \n";
    $count = 0;
    $model = new Sher_Core_Model_ActiveDrawRecord();
    echo "begin import ...\n";
    for($i=1;! feof($fp);$i++) 
    { 
        $line = fgets($fp);
        $arr = explode(',', $line);
        if(empty($arr) || count($arr)<2){
            continue;
        }
        $id = $arr[0];
        $number = sprintf("申通: %s",$arr[1]);
        echo sprintf("ID: %s, code: %s.\n", $id, $number);
        $ok = true;
        //$ok = $model->update_set($id, array('desc'=>$number, 'state'=>1));
        if($ok){
            usleep(50000);
            $count++;
        }
    } 
} else { 
    echo "open file fail!"; 
} 
fclose($fp);

echo "success! $count.\n";
