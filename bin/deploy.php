#!/usr/bin/env php
<?php
/**
 * Deploy static files
 * 同步到Qiniu qrsync
 */
system("doggy dev");

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

$conf_dir = '/Users/xiaoyi';
$from_src = '/Users/xiaoyi/Project/phenix-ui/build/packaged';
$to_src = '/Users/xiaoyi/Project/phenix/data/web/packaged';

$css_version = Doggy_Config::$vars['app.version.css_bundle_version'];
$js_version = Doggy_Config::$vars['app.version.jquery_bundle_version'];

// 复制文件
function copy_files($from_src, $to_src){
	echo "Start to copy files ... \n";
	if (empty($from_src) || empty($to_src)){
		echo "Copy dir is null!!!! \n";
		return false;
	}
	system("rm -rf $to_src/*");
	system("cp -Rf $from_src/* $to_src/");
	echo "Copy files is ok! \n";
}

// 添加版本号
function inc_version($css_version, $js_version, $to_src){
	echo "Start to inc version ... \n";
	
	$css_files = array('css/bird.min.css');
	$js_files = array('javascript/base.min.js', 'javascript/jquery_bundle.min.js', 'javascript/bird.min.js');
	
	for($i=0;$i<count($css_files);$i++){
		$file = $css_files[$i];
		$new_file = preg_replace('/min/', 'min.'.$css_version, $file);
		
		system("mv $to_src/$file $to_src/$new_file");
		
		echo "css new file: $new_file \n";
	}
	
	for($i=0;$i<count($js_files);$i++){
		$file = $js_files[$i];
		$new_file = preg_replace('/min/', 'min.'.$js_version, $file);
		
		system("mv $to_src/$file $to_src/$new_file");
		
		echo "js new file: $new_file \n";
	}
	
	echo "Inc version is ok! \n";
}

// 同步文件
// qrsync -skipsym ~/qiniu/conf.json
function deploy_sync($conf_dir){
	echo "Start to sync files ... \n";
	
	system("qrsync -skipsym $conf_dir/qiniu/conf.json");
	
	echo "Sync files is ok! \n";
}

echo "Deploying ... \n";

copy_files($from_src, $to_src);

inc_version($css_version, $js_version, $to_src);

deploy_sync($conf_dir);

echo "Deploy is OK! \n";
?>