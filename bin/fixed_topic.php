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
        'new' => 114,
    ),
    // 七嘴八舌
    array(
        'old' => 19,
        'new' => 115,
    ),
    // 深度解析
    array(
        'old' => 62,
        'new' => 112,
    ),
    array(
        'old' => 63,
        'new' => 112,
    ),
    // 行业次讯
    array(
        'old' => 15,
        'new' => 111,
    ),
    array(
        'old' => 16,
        'new' => 111,
    ),
    array(
        'old' => 17,
        'new' => 111,
    ),
);

for($i=0;$i<count($categories);$i++){
    $cate = $categories[$i];
    $criteria = array(
        'category_id' => (int)$cate['old'],
    );
    $updated = array(
        'category_id' => (int)$cate['new'],
    );

    $ok = $topic->update_set($criteria, $updated, false, true, true);
}
echo "Topic child category merge ok.\n";

// 合并父类别
$parent = array(
    //官网
    array(
        'cid' => 60,
        'fid' => 109,
    ),
    //用户
    array(
        'cid' => 61,
        'fid' => 110,
    ),
);

for($i=0;$i<count($parent);$i++){
    $cate = $parent[$i];
    $criteria = array(
        'category_id' => (int)$cate['cid'],
    );
    $updated = array(
        'fid' => (int)$cate['fid'],
    );
    $ok = $topic->update_set($criteria, $updated, false, true, true);
    
    $ok = $category->update_set((int)$cate['cid'], array('pid'=>(int)$cate['fid']), false, true, true);
}

echo "All topic merge ok.\n";

// 重算分类计数
$category = new Sher_Core_Model_Category();
$rows = $category->find(array('domain'=>Sher_Core_Util_Constant::TYPE_TOPIC));
for($i=0;$i<count($rows);$i++){
    $row = $rows[$i];
    $topic = new Sher_Core_Model_Topic();
    if(!empty($row['pid'])){
        $total = $topic->count(array('category_id' => (int)$row['_id']));
    }else{
        $total = $topic->count(array('fid' => (int)$row['_id']));
    }
    $category->update_set((int)$row['_id'], array('total_count' => (int)$total));
    
}

echo "All category remath ok.\n";

?>
