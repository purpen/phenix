<?php
/**
 * crontab 订时任务管理
 */

define('DS', DIRECTORY_SEPARATOR);
require dirname(__FILE__) . DS . 'vendor' . DS . 'autoload.php';
date_default_timezone_set('PRC');

error_reporting(E_ALL);

$crontab_config = [
    'test' => [
        'name' => 'Test',
        'cmd' => 'date',
        'output' => '/Users/tian/test.log',
        'time' => '*/5 * * * *'
    ],
    'balance_stat' => [
        'name' => '每日定时佣金结算',
        'cmd' => 'php cron_workers/balance_stat_worker.php',
        'output' => '/Users/tian/cron.log',
        'time' => '*/5 * * * *',
    ],

];

$crontab_server = new \Jenner\Zebra\Crontab\Crontab($crontab_config);
$crontab_server->start();
