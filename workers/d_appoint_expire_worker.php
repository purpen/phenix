<?php
/**
 * 实验室预约单过期处理
 * @author tianshuai
 * 15分钟过期关闭,释放预约名额
 */

$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============D-APPOINT EXPIRE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";


echo "begin deal expire d-appoint...\n";
$appoint_model = new Sher_Core_Model_DAppoint();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$time = time() - 900;
while(!$is_end){
	$query = array('state'=>Sher_Core_Model_DAppoint::STATE_PAY, 'created_on'=>array('$lt'=>$time));
	$options = array('field' => array('_id', 'state', 'created_on'), 'page'=>$page, 'size'=>$size);
	$list = $appoint_model->find($query, $options);
	if(empty($list)){
		echo "Get d-appoint list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $appoint = $list[$i];
		// 只有未付款预约单才能关闭
		if ($appoint['state'] == Sher_Core_Model_DAppoint::STATE_PAY){
			// 关闭预约单,释放名额
      try{
        $ok = $appoint_model->close_appoint((string)$appoint['_id']);
        if($ok){
          echo "success update d-appoint state:".$appoint['_id']."\n";
          $total++;     
        }else{
          echo "update d-appoint:".$appoint['_id']." faile!!!\n";
        }
      } catch (Sher_Core_Model_Exception $e) {
        echo "update d-appoint:".$appoint['_id']." faile!!!". $e->getMessage()."\n";
      }

    }else{
      echo $appoint['_id']."d-appoint state error!!!\n";
    }

	}
	if($max < $size){
		echo "d-appoint list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page d-appoint updated---------\n";
}
echo "Total $total d-appoint rows updated.\n";

echo "All d-appoint close  works done.\n";
echo "===========================D-APPOINT EXPIRE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 minute
sleep(60);
exit(0);
