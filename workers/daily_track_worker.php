<?php
/**
 * 统计在线用户并清理过期session
 * @author purpen
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

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============TRY CLEAN WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to clean...\n";

$session = new Sher_Core_Model_Session();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;

$query = array();
$options = array('field'=>array('_id','alive', 'created_on'), 'page'=>$page, 'size'=>$size);
$list = $session->find($query, $options);
if(empty($list)){
	echo "session list is null,exit......\n";
	break;
}
$max = count($list);
// 半小时内未活跃的session
$expired_time = time() - 1800;
for ($i=0; $i<$max; $i++) {
    $sid = $list[$i]['_id'];
    if (isset($list[$i]['alive'])) {
        if($list[$i]['alive'] < $expired_time){
            $session->remove($sid);
		    $total++;
            echo "success remove session id [".$sid."]..........\n";
        }
        // todo: 统计在线人数及在线会员人数
        
    } else { 
        // 如果没有过期时间，根据创建时间判断
        if($list[$i]['created_on'] < $expired_time){
            $session->remove($sid);
		    $total++;
            echo "success remove session id [".$sid."]..........\n";
        }     
    }
}

echo "clean expired session [$total] is OK! \n";

// sleep 60 seconds
sleep(60);

exit(0);
