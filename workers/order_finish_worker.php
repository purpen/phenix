<?php
/**
 * 订单完成订单
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
echo "===============ORDER EVALUATE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";


echo "begin deal evaluate orders...\n";
$order_model = new Sher_Core_Model_Orders();
$page = 1;
$size = 100;
$is_end = false;
$total = 0;
// 7天
$time = time() - 604800;
while(!$is_end){
	$query = array('status'=>Sher_Core_Util_Constant::ORDER_EVALUATE, 'delivery_date'=>array('$lt'=>$time));
	$options = array('field' => array('_id', 'user_id', 'status', 'delivery_date', 'created_on'), 'page'=>$page, 'size'=>$size);
	$list = $order_model->find($query, $options);
	if(empty($list)){
		echo "Get order list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
        $order = $list[$i];

	    // 自动收货
        try{
            $ok = $order_model->finish_order((string)$order['_id'], array('user_id'=>$order['user_id']));
            if($ok){
                echo "success update order status:".$order['_id']."\n";
                $total++;     
            }else{
                echo "update order:".$order['_id']." faile!!!\n";
            }
        } catch (Sher_Core_Model_Exception $e) {
            echo "update order:".$order['_id']." faile!!!". $e->getMessage()."\n";
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

echo "All order evaluate  works done.\n";
echo "===========================ORDER EVALUATE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 1 hour
sleep(3600);
exit(0);


