<?php
//CHANGE IF DEPLOY
$cfg_doggy_root = '/Users/night/working/taihuoniao/doggy';
$cfg_doggy_version = 'v1.3.9-dev';
$cfg_app_project_root = '/Users/night/working/taihuoniao';
$cfg_app_deploy_root = '/Users/night/working/taihuoniao/dev_root';
$cfg_app_doggyx_root = '/Users/night/working/taihuoniao/doggy/doggy-x';
/////////////////////////////////OPTIONAL//////////////////
$cfg_app_src = $cfg_app_project_root.'/src';
$cfg_app_vendor_src = $cfg_app_project_root.'/vendor';
$cfg_app_lib_src = $cfg_app_project_root.'/lib';
$cfg_app_doggyx_src = $cfg_app_doggyx_root.'/src';
$cfg_app_class_path = $cfg_app_src.':'.$cfg_app_vendor_src.':'.$cfg_app_doggyx_src.':'.$cfg_app_lib_src;
$cfg_app_rc = $cfg_app_deploy_root.'/var/doggy_app.rc';
$cfg_doggy_bootstrap = $cfg_doggy_root.'/src/Doggy.php';
