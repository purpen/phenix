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

// 合并子类别
$categories = array(
    array(
        'old' => 22,
        'new' => 21,
    ),
    array(
        'old' => 20,
        'new' => 19,
    ),
    array(
        'old' => 23,
        'new' => 19,
    ),
    array(
        'old' => 29,
        'new' => 27,
    ),
    array(
        'old' => 25,
        'new' => 27,
    ),
    array(
        'old' => 26,
        'new' => 27,
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
    $topic = new Sher_Core_Model_Topic();
    $ok = $topic->update_set($criteria, $updated, false, true, true);
    unset($topic);
}
echo "Topic child category merge ok.\n";

// 合并父类别
$parent = array(
    array(
        'cid' => 21,
        'fid' => 12,
    ),
    array(
        'cid' => 19,
        'fid' => 12,
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
    $topic = new Sher_Core_Model_Topic();
    $ok = $topic->update_set($criteria, $updated, false, true, true);
    unset($topic);
    
    $category = new Sher_Core_Model_Category();
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
    
    unset($topic);
}

echo "All category remath ok.\n";

?>