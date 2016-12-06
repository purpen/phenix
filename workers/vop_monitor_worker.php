<?php
/**
 * 开普勒监控(价格，状态等)
 * @author tianshuai
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
echo "===============VOP MONITOR WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to check...\n";

$product_mode = new Sher_Core_Model_Product();
$inventory_mode = new Sher_Core_Model_Inventory();
$vop_monitor_model = new Sher_Core_Model_VopMonitor();

$page = 1;
$size = 50;
$is_end = false;

$total = 0;
$record_count = 0;

while(!$is_end){
	$query = array('vop_id'=>array('$ne'=>null));
	$options = array('field'=>array('_id','vop_id','price'), 'page'=>$page, 'size'=>$size);
	$list = $inventory_mode->find($query, $options);
	if(empty($list)){
		echo "jd sku list is null,exit......\n";
		break;
	}
	$max = count($list);
    $item = array();

    // 是否下架
    $stat_method = 'biz.product.state.query';
    $stat_response_key = 'biz_product_state_query_response';

    // 获取价格
    $price_method = 'biz.price.sellPrice.get';
    $price_response_key = 'biz_price_sellPrice_get_response';

	for ($i=0; $i<$max; $i++) {
        $sku_id = (int)$list[$i]['_id'];
        $jd_sku_id = $list[$i]['vop_id'];
        $price = (float)$list[$i]['price'];
        $product_id = $list[$i]['product_id'];
        if(!empty($jd_sku_id)){
            $item[$jd_sku_id] = array($sku_id, $product_id, $price);
		    $total++;      
        }
	}   // endfor
    $price_sku_arr = array();
    $stat_sku_arr = array();
    foreach($item as $k=>$v){
        array_push($price_sku_arr, sprintf("J_%s", $k));
        array_push($stat_sku_arr, $k);
    }

    if(!empty($stat_sku_arr)){
    
        $price_skus = implode(',', $price_sku_arr);
        $stat_skus = implode(',', $stat_sku_arr);

        
        $stat_params = array('sku'=>$stat_skus);
        $stat_json = !empty($stat_params) ? json_encode($stat_params) : '{}';
        $stat_result = Sher_Core_Util_Vop::fetchInfo($stat_method, array('param'=>$stat_json, 'response_key'=>$stat_response_key));

        if(!empty($stat_result['code'])){
            echo "获取上架接口异常! \n";
        }
        if(empty($stat_result['data']['success'])){
            echo $stat_result['data']['resultMessage']."\n";
        }

        for($i=0;$i<count($stat_result['data']['result']);$i++){
            $p = $stat_result['data']['result'][$i];
            if(isset($item[$p['sku']]) && $p['state'] != 1){   // 下架提醒
                
                $has_one = $vop_monitor_model->first(array('sku_id'=>$item[$p['sku']][0], 'evt'=>1));
                if($has_one){
                    $ok = $vop_monitor_model->update_set((string)$has_one['_id'], array('stat'=>0));
                }else{
                    $row = array(
                        'sku_id' => (int)$item[$p['sku']][0],
                        'product_id' => (int)$item[$p['sku']][1],
                        'jd_sku_id' => (int)$p['sku'],
                        'stat' => 0,
                    );
                    $ok = $vop_monitor_model->create($row);
                }

                if(!$ok){
                    continue;
                }
                $record_count++;

            }

        }   // endfor


        // 查询价格
        $price_params = json_encode(array('sku'=>$price_skus));
        $price_result = Sher_Core_Util_Vop::fetchInfo($price_method, array('param'=>$price_params, 'response_key'=>$price_response_key));


        if(!empty($price_result['code'])){
            echo "获取价格失败: ".$price_result['msg']."\n";
        }

        for($i=0;$i<count($price_result['data']['result']);$i++){
            $p = $price_result['data']['result'][$i];
            $protocol_price = (float)$p['price'];
            $jd_price = (float)$p['jdPrice'];
            
            // 记录有变化的产品
            if(isset($item[$p['skuId']]) && $item[$p['skuId']][2]!=$jd_price){
                $has_one = $vop_monitor_model->first(array('sku_id'=>$item[$p['skuId']][0], 'evt'=>1));
                if($has_one){
                    $ok = $vop_monitor_model->update_set((string)$has_one['_id'], array('protocol_price'=>$protocol_price, 'new_price'=>$jd_price));
                }else{
                    $row = array(
                        'sku_id' => (int)$item[$p['skuId']][0],
                        'product_id' => (int)$item[$p['skuId']][1],
                        'jd_sku_id' => (int)$p['skuId'],
                        'protocol_price' => $protocol_price,
                        'price' => $item[$p['skuId']][2],
                        'new_price' => $jd_price,
                    );
                    $ok = $vop_monitor_model->create($row);
                }

                if(!$ok){
                    continue;
                }
                $record_count++;
            }
        }   // endfor

    }   // endif

	if($max < $size){
		$is_end = true;
		echo "inventory vop monitor begging list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "monitor vop count: [$total], record_count: [$record_count] is OK! \n";
// sleep 1 hour
sleep(3600);
exit(0);
