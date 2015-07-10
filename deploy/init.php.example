<?php
/**
 * 初始化加载环境变量
 * @author purpen
 *
 * CHANGE IF DEPLOY
 */
$cfg_doggy_root = '/Users/xiaoyi/project/doggy';
$cfg_doggy_version = 'v1.4.0';
$cfg_app_project_root = '/Users/xiaoyi/project/phenix';
$cfg_app_deploy_root = '/Users/xiaoyi/project/phenix/dev_root';
$cfg_doggyx_root = '/Users/xiaoyi/Project/doggy-x';

/////////////////////////////////OPTIONAL//////////////////
$cfg_app_src = $cfg_app_project_root.'/src';
$cfg_app_vendor_src = $cfg_app_project_root.'/vendor';
$cfg_doggyx_src = $cfg_doggyx_root.'/src';
$cfg_app_class_path = $cfg_app_src.':'.$cfg_doggyx_src.':'.$cfg_app_vendor_src;
$cfg_app_rc = $cfg_app_deploy_root.'/var/doggy_app.rc';
$cfg_doggy_rc = $cfg_doggy_root.'/src/Doggy.php';

$cfg_vendor_autoload = $cfg_app_vendor_src.'/autoload.php';
$cfg_resque_src = $cfg_app_vendor_src.'/chrisboulton/php-resque/';
$cfg_resque_src = $cfg_app_vendor_src.'/chrisboulton/php-resque-scheduler/';

define('DOGGY_VERSION', $cfg_doggy_version);
define('DOGGY_APP_ROOT', $cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH', $cfg_app_class_path);

require $cfg_doggy_rc;

@require $cfg_app_rc;
@require $cfg_vendor_autoload;

?>