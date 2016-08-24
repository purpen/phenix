#!/usr/bin/env php
<?php
/**
 * fix topic try_id => (int)try_id
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

echo "fixed stuff ...\n";

$stuff_model = new Sher_Core_Model_Stuff();
$page = 1;
$size = 100;
$is_end = false;
$total = 0;
/*
while(!$is_end){
	$query = array('from_to'=>6, 'published'=>1);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
        $id = $list[$i]['_id'];
        $view_count = $list[$i]['view_count'];
        if($view_count<50000){
            $rand = rand(3000, 5000);
            $ok = $stuff_model->inc_counter('view_count', $rand, $id);
            $total++;
        }       
    }
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
 */

$stuff_ids = "113308,113087,113140,113146,112743,112782,112811,112813,113132,113134,112724,112865,112891,112939,112955,112725,113299,113300,113305,113306,113309,112791,112889,112997,113046,113057,113078,11312,113147,113149,112710,112799,112880,112887,112937,112982,113041,113104,113142,113143,112711,113304,113307,112784,112858,112931,113139,112695,112767,112806,112873,112921,112929,112940,112954,112971,112986,112993,112995,113098,113100,113136,113310,113312,113138,113141,113144,113150,112752,112847,113032,112728,112703,112696,113047,112801,112882,112786,113246,112702,112719,113089,112996,112768,113017,112714,112911,112949,112992,113126,113311,112721,112846,112746,112890,112881,112828,113125,113000,112682";

$stuff_arr = explode(',', $stuff_ids);
for($i=0;$i<count($stuff_arr);$i++){
    $id = (int)$stuff_arr[$i];
    $stuff = $stuff_model->load($id);
    if(empty($stuff)) continue;
    $ok = $stuff_model->update_set($id, array('is_prize'=>1));
    if($ok){
        echo "success! $id.\n";
    }else{
        echo "fail! $id.\n";
    }

}


$stuff_stick_ids = "113140,113146,113132,112865,112955,112725,113299,113300,113306,112889,112997,113057,113078,113147,113149,112710,112982,113104,113142,113143,112858,113139,112695,112995,113100,113136,113312,113138,113141,113017";

$stuff_stick_arr = explode(',', $stuff_stick_ids);
for($i=0;$i<count($stuff_stick_arr);$i++){
    $id = (int)$stuff_stick_arr[$i];

    if(empty($stuff)) continue;
    $ok = $stuff_model->update_set($id, array('stick'=>1));
    if($ok){
        echo "success stick! $id.\n";
    }else{
        echo "fail stick! $id.\n";
    }

}


echo "All stuff count: $total update ok.\n";
?>
