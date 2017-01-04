<?php
/**
 * crontab 订时任务管理
 */

define('DS', DIRECTORY_SEPARATOR);
require dirname(__FILE__) . DS . 'vendor' . DS . 'autoload.php';
date_default_timezone_set('PRC');

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
    'balance_stat' => [
        'name' => '每日定时佣金结算',
        'cmd' => sprintf("%s cron_workers/balance_stat_worker.php", $php_path),
        'output' => '/www/phenix/logs/crontab/balance_stat.log',
        'time' => '*/1 * * * *',
    ],

];

$crontab_server = new \Jenner\Zebra\Crontab\Crontab($crontab_config);
$crontab_server->start();
