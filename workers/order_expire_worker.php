<?php
/**
 * 订单过期处理
 * @author tianshuai
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
echo "===============ORDER EXPIRE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";


echo "begin deal expire orders...\n";
$order_model = new Sher_Core_Model_Orders();
$page = 1;
$size = 1000;
$is_end = false;
$total = 0;
$time = time();
while(!$is_end){
	$query = array('status'=>Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT, 'expired_time'=>array('$lt'=>$time));
	$options = array('field' => array('_id', 'user_id', 'status', 'expired_time', 'created_on'), 'page'=>$page, 'size'=>$size);
	$list = $order_model->find($query, $options);
	if(empty($list)){
		echo "Get order list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
    $order = $list[$i];
    $jd_order_id = isset($order['jd_order_id']) ? $order['jd_order_id'] : null;
		// 未支付订单才允许关闭
		if ($order['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			// 关闭订单
      try{
        $ok = $order_model->close_order($order['_id'], array('user_id'=>$order['user_id'], 'jd_order_id'=>$jd_order_id));
        if($ok){
          echo "success update order status:".$order['_id']."\n";
          $total++;     
        }else{
          echo "update order:".$order['_id']." faile!!!\n";
        }
      } catch (Sher_Core_Model_Exception $e) {
        echo "update order:".$order['_id']." faile!!!". $e->getMessage()."\n";
      }

    }else{
      echo $order['_id']."order status error!!!\n";
    }

	}
	if($max < $size){
		echo "order list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "Page $page order updated---------\n";
}
echo "Total $total order rows updated.\n";

echo "All order close  works done.\n";
echo "===========================ORDER EXPIRE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 minute
sleep(60);
exit(0);
