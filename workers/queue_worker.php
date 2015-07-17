<?php
/**
 * 邮件任务队列监测
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
echo "===============QUEUE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to query...\n";

$page = 1;
$size = 1000;
$end_id = 0;
$is_end = false;
try{
    $edm = new Sher_Core_Model_Edm();
    $row = $edm->first(array('state'=>1));
    
    if(!empty($row)){
        $edm_id = $row['_id'];
        // 更新状态
        $edm->mark_set_send($edm_id);
        
        // 设置任务
        $emailing = new Sher_Core_Model_Emailing();
        
        $task = new Sher_Core_Model_TaskQueue();
        while(!$is_end){
            if($end_id){
                $query['_id'] = array(
                    '$gt' => $end_id,
                );
            }
            $query['state'] = 1;
        
            $options = array('page'=>$page,'size'=>$size);
            $list = $emailing->find($query, $options);
        	if(empty($list)){
        		echo "Get email list is null,exit......\n";
                $is_end = true;
        		break;
        	}
        	$max = count($list);
            for($i=0;$i<$max;$i++){
                $task->queue_email($list[$i]['email'], $list[$i]['name'], $edm_id);
                $end_id = $list[$i]['_id'];
            }
        	if($max < $size){
        		echo "Email list is end!!!!!!!!!,exit.\n";
                $is_end = true;
        		break;
        	}
        }
    }else{
        echo "Edm is Null!\n";
    }
}catch(Sher_Core_Model_Exception $e){
    echo "Send mail failed: ".$e->getMessage();
}

echo "===========================QUEUE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
// sleep 3 minute
sleep(180);
exit(0);