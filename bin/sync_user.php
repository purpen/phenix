#!/usr/bin/env php
<?php
/**
 * 同步用户信息到sso
 * @author tianshuai
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

echo "begin export user info...\n";
$user_model = new Sher_Core_Model_User();
$page = 1;
$size = 500;
$is_end = false;
$total = 0;
$begin_time = strtotime('2014-01-01');
$end_time = strtotime('2018-12-01');

while(!$is_end){
	$query = array('created_on'=>array('$gte'=>$begin_time, '$lte'=>$end_time));
	$options = array('page'=>$page,'size'=>$size);
	$list = $user_model->find($query, $options);
	if(empty($list)){
		echo "get user list is null,exit......\n";
		break;
	}
	$max = count($list);
  for ($i=0; $i < $max; $i++) {
    $user = $list[$i];
    if (!isset($user['account'])) {
      echo "account not exist! .\n";
      continue;
    }

    if (!isset($user['kind'])) {
      $user['kind'] = 0;
    }

    $phone = '';
    $email = '';
    $isMobile = Sher_Core_Helper_Util::isMobile($user['account']);
    if ($isMobile) {
      $phone = $user['account'];
    }

    $isEmail = Sher_Core_Helper_Util::isEmail($user['account']);
    if ($isEmail) {
      $email = $user['account'];
    }

    $status = $user['state'];
    if ($user['state'] == 2) {
      $status = 1;
    } elseif ($user['state'] == -1) {
      $status = 0;
    }

    // 请求sso系统
    $sso_params = array(
        'account' => $user['account'],
        'phone' => $phone,
        'email' => $email,
        'password' => $user['password'],
        'wx_union_id' => isset($user['wx_union_id']) ? $user['wx_union_id'] : '',
        'wx_uid' => isset($user['wx_open_id']) ? $user['wx_open_id'] : '',
        'qq_uid' => isset($user['qq_uid']) ? $user['qq_uid'] : '',
        'wb_uid' => isset($user['sina_uid']) ? $user['sina_uid'] : '',
        'status' => $status,
        'kind' => $user['kind'],
    );
    $new_sso_params = Sher_Core_Helper_Util::api_param_encrypt($sso_params);
    $sso_url = Doggy_Config::$vars['app.sso']['url'].'auth/sync';

    $sso_result = Sher_Core_Helper_Util::request($sso_url, $new_sso_params, 'POST');
    $sso_result = Sher_Core_Helper_Util::object_to_array(json_decode($sso_result));

    if (!isset($sso_result['code'])) {
        $r = Sher_Core_Util_View::load_block('sso_request_fail');
        $r = $r . ',' . $user['_id'];
        Sher_Core_Util_View::push_block_content('sso_request_fail', $r);
        echo "请求用户系统失败.\n";
        continue;
    }

    if ($sso_result['code'] == 400) {
        $z = Sher_Core_Util_View::load_block('sso_create_fail');
        $z = $z . ',' . $user['_id'];
        Sher_Core_Util_View::push_block_content('sso_create_fail', $z);
        echo "$sso_result[message].\n";
        continue;
    }

    if ($sso_result['code'] == 201) {
        $z = Sher_Core_Util_View::load_block('sso_user_exist');
        $z = $z . ',' . $user['_id'];
        Sher_Core_Util_View::push_block_content('sso_user_exist', $z);
        echo "$sso_result[message].\n";
        continue;
    }

    $total++;

	}
	if($max < $size){
		echo "user list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}
echo "total $total user rows sync over.\n";

echo "All user sync done.\n";

