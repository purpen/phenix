<?php
/**
 * crontab 订时任务管理
 */

define('DS', DIRECTORY_SEPARATOR);
require dirname(__FILE__) . DS . 'vendor' . DS . 'autoload.php';
date_default_timezone_set('Asia/shanghai');

error_reporting(E_ALL);

$php_path = "/usr/bin/env php";
$pro_path = "/opt/project/phenix";

$crontab_config = [
    'test' => [
        'name' => 'Test',
        'cmd' => 'date',
        'output' => '/home/tianxiaoyi/test.log',
        'time' => '*/5 * * * *'
    ],
    'balance_record' => [
        'name' => '每日定时佣金结算',
        'cmd' => sprintf("%s cron_workers/balance_record_worker.php", $php_path),
        'output' => '/www/phenix/logs/crontab/balance_record.log',
        'time' => '*/5 * * * *',
    ],
    'balance_stat' => [
        'name' => '联盟账户结算每日/周/月统计',
        'cmd' => sprintf("%s cron_workers/balance_stat_worker.php", $php_path),
        'output' => '/www/phenix/logs/crontab/balance_stat.log',
        'time' => '*/5 * * * *',
    ],
    'clean_bonus' => [  // 每月1号执行
        'name' => '定期清理过期未使用红包',
        'cmd' => sprintf("%s cron_workers/clean_bonus_worker.php", $php_path),
        'output' => '/www/phenix/logs/crontab/clean_bonus.log',
        'time' => '* * * 1 *',
    ],
    'stat' => [ // 每天12：30执行
        'name' => '数据统计',
        'cmd' => sprintf("%s cron_workers/stat_worker.php", $php_path),
        'output' => '/www/phenix/logs/crontab/stat.log',
        'time' => '0 30 0 * *',
    ],

];

$crontab_server = new \Jenner\Zebra\Crontab\Crontab($crontab_config);
$crontab_server->start();
