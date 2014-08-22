#!/usr/bin/env php
<?php
/**
 * Resque定时任务启动脚本
 * @author purpen
 */
// 让程序无限制的执行下去
set_time_limit(0);

$config_file =  dirname(dirname(__FILE__)).'/deploy/init.php';
if (!file_exists($config_file)) {
    die("Can't find config file: $config_file\n");
}

require_once $config_file;
require_once $cfg_resque_src.'lib/Resque.php';
require_once $cfg_resque_src.'lib/Resque/Worker.php';

Resque::setBackend(Doggy_Config::$vars['app.redis_host']);


function build_clean_task(){
	Doggy_Log_Helper::debug("Enqueue clean jobs!");
	// 添加任务到队列
	Resque::enqueue('cleaner', 'Sher_Core_Jobs_Clean');
	
	Doggy_Log_Helper::debug("Enqueue job ok!");
}

// 开始执行
build_clean_task();

?>
