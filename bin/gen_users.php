#!/usr/bin/env php
<?php
/**
 * fix user name => nickname
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

// 批量创建账号
function batch_create_user($start=1000){
	echo "Create batch the users ...\n";

    $user = new Sher_Core_Model_User();
    $data = array();
    $account_prefix = array(138,168,188,189,186,158);
    $cities = array('北京','深圳','广州','上海','杭州','南京','苏州','其他');
    $step = 1;
    $end = $start + 500;
    for($i=$start; $i<=$end; $i+=$step){
        $account = (int)$account_prefix[array_rand($account_prefix, 1)].'00000000' + $i;
        $city = $cities[array_rand($cities, 1)];
        $email = (string)$account.'@139.com';
        $data = array(
            'account'  => (string)$account,
            'password' => sha1('thn456321'),
            'nickname' => (string)$account,
            'state'    => Sher_Core_Model_User::STATE_OK,
            'role_id'  => Sher_Core_Model_User::ROLE_USER,
            'quality'  => 1,
            'sex'   => 1,
            'city'  => $city,
            'email' => $email,
            'kind'  => 9,
        );
        $profile = $user->get_profile();
        $profile['phone'] = (string)$account;
        $profile['job'] = '设计师';
        $data['profile'] = $profile;

        try{
            $ok = $user->create($data);
            
            if($ok){
                echo "Create the user[$account] is ok!...num: $i...\n";
            }else{
                echo "Create the user[$account] is fail!...num: $i...\n";
            }
            
        }catch(Sher_Core_Model_Exception $e){
            echo "Create the user[$account] failed: ".$e->getMessage();
        }
        
        $step = rand(1,8);
        
        sleep($step);
    }
}

$start = 1000;
batch_create_user($start);