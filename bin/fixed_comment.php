#!/usr/bin/env php
<?php
/**
 * 修改评论target_id
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

echo "Begin fix comment...\n";

$new_target_id = 111072;

$comment_model = new Sher_Core_Model_Comment();
$comments = $comment_model->find(array('target_id'=>111028, 'type'=>2));

for($i=0;$i<count($comments);$i++){
    $id = (string)$comments[$i]['_id'];
    $target_id = $comments[$i]['target_id'];
    $content = $comments[$i]['content'];

    //$ok = $comment_model->update_set($id, array('target_id'=>$new_target_id));
}
echo " ok.\n";

