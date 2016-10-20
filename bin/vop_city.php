#!/usr/bin/env php
<?php
/**
 * 导出京东收货地址
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
@require 'autoload.php';
@require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "--------Begin export vop city-----------\n";

function export_vop_city(){

    $method = 'biz.address.allProvinces.query';
    $response_key = 'biz_address_allProvinces_query_response';
    $params = array();
    $json = !empty($params) ? json_encode($params) : '{}';
    $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
    $provinces = $result['data']['result'];

    $china_city_model = new Sher_Core_Model_ChinaCity();

    //print_r($provinces);
    foreach($provinces as $k=>$v){

        $oid = (int)$v;
        $name = $k;
        if(empty($oid)) continue;

        $has_one = $china_city_model->first(array('oid'=>$oid, 'layer'=>1));

        if($has_one){
            $ok = $china_city_model->update_set((string)$has_one['_id'], array('name'=>$name, 'pid'=>0, 'layer'=>1));
        
        }else{
            $ok = $china_city_model->create(array('oid'=>$oid, 'name'=>$name, 'layer'=>1));
        }

        if(!$ok) continue;

        echo sprintf("level-1:%s.\n", $name);

        // 二级
        $method = 'biz.address.citysByProvinceId.query';
        $response_key = 'biz_address_citysByProvinceId_query_response';
        $params = array('id'=>$oid);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if($result['code'] !=0 || $result['data']['success']!=1) continue;
        $cities = $result['data']['result'];
        foreach($cities as $a=>$b){

            $oid_1 = (int)$b;
            $name = $a;
            if(empty($oid_1)) continue;

            $has_one = $china_city_model->first(array('oid'=>$oid_1, 'layer'=>2));

            if($has_one){
                $ok = $china_city_model->update_set((string)$has_one['_id'], array('name'=>$name, 'pid'=>$oid, 'layer'=>2));
            }else{
                $ok = $china_city_model->create(array('oid'=>$oid_1, 'pid'=>$oid, 'name'=>$name, 'layer'=>2));
            }


            if(!$ok) continue;
            echo sprintf("level-2:%s.\n", $name);

            // 三级
            $method = 'biz.address.countysByCityId.query';
            $response_key = 'biz_address_countysByCityId_query_response';
            $params = array('id'=>$oid_1);
            $json = !empty($params) ? json_encode($params) : '{}';
            $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
            if($result['code'] !=0 || $result['data']['success']!=1) continue;
            $country = $result['data']['result'];

            foreach($country as $c=>$d){
                $oid_2 = (int)$d;
                $name = $c;
                if(empty($oid_2)) continue;

                $has_one = $china_city_model->first(array('oid'=>$oid_2, 'layer'=>3));

                if($has_one){
                    $ok = $china_city_model->update_set((string)$has_one['_id'], array('name'=>$name, 'pid'=>$oid_1, 'layer'=>3));
                }else{
                    $ok = $china_city_model->create(array('oid'=>$oid_2, 'pid'=>$oid_1, 'name'=>$name, 'layer'=>3));
                }

                if(!$ok) continue;

                // 四级
                $method = 'biz.address.townsByCountyId.query';
                $response_key = 'biz_address_townsByCountyId_query_response';
                $params = array('id'=>$oid_2);
                $json = !empty($params) ? json_encode($params) : '{}';
                $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

                if($result['code'] !=0 || $result['data']['success']!=1) continue;
                $towns = $result['data']['result'];

                foreach($towns as $e=>$f){
                    $oid_3 = (int)$d;
                    $name = $c;
                    if(empty($oid_3)) continue;

                    $has_one = $china_city_model->first(array('oid'=>$oid_3, 'layer'=>3));

                    if($has_one){
                        $ok = $china_city_model->update_set((string)$has_one['_id'], array('name'=>$name, 'pid'=>$oid_2, 'layer'=>4));
                    }else{
                        $ok = $china_city_model->create(array('oid'=>$oid_3, 'pid'=>$oid_2, 'name'=>$name, 'layer'=>4));
                    }

                    if(!$ok) continue;                   
                
                }   // endfor 4

            
            }   // endfor 3

        }   // endfor 2

    }   // endfor 1
}

//export_vop_city();

echo "---------End export vop city---------------\n";

