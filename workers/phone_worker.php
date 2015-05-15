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
echo "===============PHONE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Create new user ...\n";

$user = new Sher_Core_Model_User();
$phone = new Sher_Core_Model_Phone();

$data = array();

$countor = new Sher_Core_Model_Countor();
$last_row = $countor->pop(Sher_Core_Util_Constant::PHONE_AUTO_GET_COUNT);
// 第一次新增记录
if(empty($last_row)){
    $ok = $countor->inc(Sher_Core_Util_Constant::PHONE_AUTO_GET_COUNT, 'total_count', 1);
    $last_row = $countor->load(Sher_Core_Util_Constant::PHONE_AUTO_GET_COUNT);
}
if(empty($last_row)){
    echo "Get phone counter failed and exit!!! \n";
    exit(0);
}

try{
    $row = $phone->load((int)$last_row['total_count']);
    if(!empty($row)){
        $account = $row['phone'];
        $email = (string)$account.'@qq.com';
        $data = array(
            'account'  => (string)$account,
            'password' => sha1('thn#456321'),
            'nickname' => (string)$account,
            'state'    => Sher_Core_Model_User::STATE_OK,
            'role_id'  => Sher_Core_Model_User::ROLE_USER,
            'quality'  => 1,
            'city'     => '',
            'email'    => $email,
            'kind'     => 6,
        );
        $profile = $user->get_profile();
        $profile['phone'] = (string)$account;
        $profile['job'] = '';

        $data['profile'] = $profile;
    
        $ok = $user->create($data);
    
        if($ok){
            echo "Create the phone[$account] is ok!...\n";
        }else{
            echo "Create the phone[$account] is fail!...\n";
        }
    }
    
}catch(Sher_Core_Model_Exception $e){
    echo "Create the phone[$account] failed: ".$e->getMessage();
}

echo "===========================PHONE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
$hr = date('G');
if($hr >= 9 && $hr <= 23){
    $time = rand(5, 10);
}else{
    $time = rand(300, 600);
}
// sleep N minute
sleep($time);
exit(0);