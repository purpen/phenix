#!/usr/bin/env php
<?php
/**
 * fix product stage => process_voted,process_presaled,process_saled
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

echo "Prepare to fix product stage ...\n";
$product = new Sher_Core_Model_Product();
$tag_model = new Sher_Core_Model_SceneTags();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
	$query = array('stage'=>16);
	$options = array('field' => array('_id','category_tags', 'stage'),'page'=>$page,'size'=>$size);
	$list = $product->find($query,$options);
	if(empty($list)){
		echo "get product list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i<$max; $i++) {
		
        $data = $list[$i];
        $id = $data['_id'];
        if(empty($data['category_tags'])) continue;
        $new_tag_arr = array();
        for($j=0;$j<count($data['category_tags']);$j++){
            $tag = $tag_model->load((int)$data['category_tags'][$j]);
            if(empty($tag)) continue;
            array_push($new_tag_arr, $tag['title_cn']);
        }
        print_r($new_tag_arr);
        if(!empty($new_tag_arr)){
            $ok = true;
            //$ok = $product->update_set($id, array('category_tags'=>$new_tag_arr));
            if($ok){
                echo "update ok $id .\n";
		        $total++;
            }else{
                echo "update fail $id .\n";
            }
        }
		
	}
	
	if($max < $size){
		echo "product list is end!!!!!!!!!,exit.\n";
		break;
	}
	
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total product rows updated.\n";

echo "All product fix done.\n";
?>
