#!/usr/bin/env php
<?php
/**
 * 合并话题分类
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

echo "Prepare to merge topic category...\n";


$category = new Sher_Core_Model_Category();
$topic = new Sher_Core_Model_Topic();

// 合并子类别
$categories = array(
    // 产品评测
    array(
        'old' => 18,
        'new' => 242,
        'fid' => 238,
    ),
    // 七嘴八舌
    array(
        'old' => 61,
        'new' => 243,
        'fid' => 238,
    ),
    array(
        'old' => 59,
        'new' => 243,
        'fid' => 238,
    ),
    // 深度解析
    array(
        'old' => 121,
        'new' => 240,
        'fid' => 237,
    ),
    array(
        'old' => 122,
        'new' => 240,
        'fid' => 237,
    ),
    // 行业资讯
    array(
        'old' => 21,
        'new' => 239,
        'fid' => 237,
    ),
    array(
        'old' => 87,
        'new' => 239,
        'fid' => 237,
    ),
    array(
        'old' => 15,
        'new' => 239,
        'fid' => 237,
    ),
);

for($i=0;$i<count($categories);$i++){
    $cate = $categories[$i];
    $criteria = array(
        'category_id' => (int)$cate['old'],
    );
    $updated = array(
        'category_id' => (int)$cate['new'],
        'fid' => (int)$cate['fid'],
    );

    $ok = true;
    //$ok = $topic->update_set($criteria, $updated, false, true, true);
}
echo "Topic child category merge ok.\n";


// 重算分类计数
$category = new Sher_Core_Model_Category();
$rows = $category->find(array('domain'=>Sher_Core_Util_Constant::TYPE_TOPIC));
for($i=0;$i<count($rows);$i++){
    $row = $rows[$i];
    if(!empty($row['pid'])){
        $total = $topic->count(array('category_id' => (int)$row['_id']));
    }else{
        $total = $topic->count(array('fid' => (int)$row['_id']));
    }
    //$category->update_set((int)$row['_id'], array('total_count' => (int)$total));
    
}

echo "All category remath ok.\n";

?>
