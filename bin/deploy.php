#!/usr/bin/env php
<?php
/**
 * Deploy static files
 * 同步到Qiniu qrsync
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
@require $cfg_app_rc;

set_time_limit(0);

$from_src = '/Users/xiaoyi/Project/phenix-ui/build/packaged';
$to_src = '/Users/xiaoyi/Project/phenix/data/web/packaged';

$css_version = Doggy_Config::$vars['app.version.css_bundle_version'];
$js_version = Doggy_Config::$vars['app.version.jquery_bundle_version'];

// 复制文件
function copy_files($from_src, $to_src){
	echo "Start to copy files ... \n";
	system("rm -rf $to_src/*");
	system("cp -rf $from_src/* $to_src/*");
	echo "Copy files is ok! \n";
}

// 添加版本号
function inc_version($css_version, $js_version){
	echo "Start to inc version ... \n";
	
	echo "Inc version is ok! \n";
}

// 同步文件
// qrsync -skipsym ~/qiniu/conf.json
function deploy_sync(){
	echo "Start to sync files ... \n";
	
	echo "Sync files is ok! \n";
}

echo "Deploying ... \n";
copy_files($from_src, $to_src);

inc_version();

deploy_sync();

echo "Deploy is OK! \n";
?>