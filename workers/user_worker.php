<?php
/**
 * 批量更新账号
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

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============User WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Create new user ...\n";
$sleep_time = 900;
$user = new Sher_Core_Model_User();

$data = array();
$account_prefix = array(138,168,188,189,186,158);
$cities = array('北京','深圳','广州','上海','杭州','南京','苏州','其他','');

$countor = new Sher_Core_Model_Countor();
$last_row = $countor->pop(Sher_Core_Util_Constant::USER_AUTO_GEN_COUNT);
// 第一次新增记录
if(empty($last_row)){
    $ok = $countor->inc(Sher_Core_Util_Constant::USER_AUTO_GEN_COUNT, 'total_count', 500);
    $last_row = $countor->load(Sher_Core_Util_Constant::USER_AUTO_GEN_COUNT);
}
if(empty($last_row)){
    echo "Get user counter failed and exit!!! \n";
    sleep($sleep_time);
    exit(0);
}

$current_count = $last_row['total_count'];

try{
    $account = (int)$account_prefix[array_rand($account_prefix, 1)].'00000000' + $current_count;
    $city = $cities[array_rand($cities, 1)];
    $email = (string)$account.'@qq.com';
    $data = array(
        'account'  => (string)$account,
        'password' => sha1('thn#456321'),
        'nickname' => (string)$account,
        'state'    => Sher_Core_Model_User::STATE_OK,
        'role_id'  => Sher_Core_Model_User::ROLE_USER,
        'quality'  => 1,
        'city'     => $city,
        'email'    => $email,
        'kind'     => 9,
    );
    $profile = $user->get_profile();
    $profile['phone'] = (string)$account;
    $profile['job'] = '';

    $data['profile'] = $profile;
    
    //$ok = $user->create($data);
    $ok = true;
    
    if($ok){
        echo "Create the user[$account] is ok!...\n";
    }else{
        echo "Create the user[$account] is fail!...\n";
    }
    
}catch(Exception $e){
    echo "Create the user[$account] failed: ".$e->getMessage();
}

echo "===========================USER WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
$hr = date('G');
if($hr >= 9 && $hr <= 23){
    $time = rand(5, 30);
}else{
    $time = rand(300, 600);
}
// sleep N minute
sleep($time);
exit(0);
