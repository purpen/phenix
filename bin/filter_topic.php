#!/usr/bin/env php
<?php
/**
 * 过滤并删除垃圾贴子
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
ini_set('memory_limit','512M');

echo "set topic  fields ...\n";
$redis = new Sher_Core_Cache_Redis();
$access_token = $redis->get('baidu_api_access_token');
echo "baudu ai access_token: $access_token.\n";

$result = check_topic('买个a货爱马仕手表一般哪里有卖，多少钱');
print_r($result);



$topic_model = new Sher_Core_Model_Topic();
$page = 1;
$size = 100;
$is_end = true;
$total = 0;
while(!$is_end){
	$query = array('deleted'=>0);
	$options = array('field' => array('_id', 'deleted', 'status'),'page'=>$page,'size'=>$size);
	$list = $topic_model->find($query, $options);
	if(empty($list)){
		echo "get topic list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $item = $list[$i];
    $ok = true;
		//$ok = $topic_model->mark_remove($item['_id']);
    if ($ok) {
 		  echo "del topic[".$item['_id']."]..........\n";
		  $total++;   
    }
	}
	if($max < $size){
		echo "topic list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

function check_topic($content) {
  $result = array();
  $result['success'] = false;
  $result['message'] = '';
  $url = 'https://aip.baidubce.com/rest/2.0/antispam/v2/spam';
  global $access_token;
  $res = Sher_Core_Helper_Util::request($url, array('access_token'=>$access_token, 'content'=>$content), 'POST');
  if (!$result) {
    $result['message'] = 'request baidu error!';
    return $result;
  }
  $res = json_decode($res, true);
  if (isset($res['error_code'])) {
    // 重新获取access_token
    if ($res['error_code'] == 110) {
      $ftk = fetch_token();
      if ($ftk['success']) {
        return check_topic($content);
      }else{
        $result['message'] = $ftk['message'];
        return $result;
      }
    }
    $result['message'] = $res['error_msg'];
    return $result;
  }
  $result['success'] = true;
  $result['data'] = $res;
  return $result;
}

// 获取百度access_token
function fetch_token() {
  $result = array(
    'success' => false,
    'message' => '',
  );
  $url = 'https://aip.baidubce.com/oauth/2.0/token';
  $param = array(
    'grant_type' => 'client_credentials',
    'client_id' => 'lQhM3YxbwEfh1cds9Yg9UQfR',
    'client_secret' => '0lj9NQnAYzLyGQQSZR0cQSe2SOlvpiXN',
  );
  $res = Sher_Core_Helper_Util::request($url, $param, 'POST');
  if (!$res) {
    $result['message'] = 'fetch access_token false!';
    return $result;
  }
  $res = json_decode($res, true);
  if (isset($res['error'])) {
    $result['message'] = $res['error_description'];
    return $result;
  }
  if(isset($res['access_token'])) {
    global $redis;
    $redis->set('baidu_api_access_token', $res['access_token'], $res['expires_in']);
    echo "set baidu_access_token success";
    $result['success'] = true;
    return $result;
  }
  return $result;
}

echo "del topics is OK! count $total.. \n";
