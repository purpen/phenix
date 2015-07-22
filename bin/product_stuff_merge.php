#!/usr/bin/env php
<?php
/**
 *  商品智品库合并
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

echo "cpoy stuff to product ...\n";

$model = new Sher_Core_Model_Stuff();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
while(!$is_end){
  $fid = Doggy_Config::$vars['app.topic.idea_category_id'];
	$query = array('fid'=>(int)$fid);
	$options = array('page'=>$page,'size'=>$size);
	$list = $model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $data = array();
    $id = $list[$i]['_id'];
    $data['old_stuff_id'] = $id;
    $data['title'] = $list[$i]['title'];
    $data['category_id'] = $list[$i]['category_id'];
    $short_title = isset($list[$i]['short_title'])?$list[$i]['short_title']:null;
    $description = isset($list[$i]['description'])?$list[$i]['description']:null;
    $tags = isset($list[$i]['tags'])?$list[$i]['tags']:array();
    $like_tags = isset($list[$i]['like_tags'])?$list[$i]['like_tags']:array();
    $cover_id = isset($list[$i]['cover_id'])?$list[$i]['cover_id']:null;

    $view_count = $list[$i]['view_count'];
    $favorite_count = $list[$i]['favorite_count'];
    $love_count = $list[$i]['love_count'];
    $asset_count = $list[$i]['asset_count'];
    // 虚拟喜欢数
    $invented_love_count = isset($list[$i]['invented_love_count'])?$list[$i]['invented_love_count']:0;
    $comment_count = $list[$i]['comment_count'];
    $last_love_users = isset($list[$i]['last_love_users'])?$list[$i]['last_love_users']:array();
    $published = $list[$i]['published'];
    // 推荐 0 默认; 1 编辑推荐; 2 推荐到首页
    $stick = isset($list[$i]['stick'])?$list[$i]['stick']:0;
    // 精选
    $featured = isset($list[$i]['featured'])?$list[$i]['featured']:0;
    $random = isset($list[$i]['random'])?$list[$i]['random']:0;

    // 关联产品
    $fever_id = isset($list[$i]['fever_id'])?$list[$i]['fever_id']:0;
    // 是否审核
    $verified = isset($list[$i]['verified'])?$list[$i]['verified']:1;
    // 品牌ID
    $cooperate_id = isset($list[$i]['cooperate_id'])?$list[$i]['cooperate_id']:null;
    $team_introduce = isset($list[$i]['team_introduce'])?$list[$i]['team_introduce']:null;
    // 品牌名称
    $cooperate_id = isset($list[$i]['brand'])?$list[$i]['brand']:null;
    // 设计师
		$designer = isset($list[$i]['designer'])?$list[$i]['designer']:null;
    // 所属国家
		$country = isset($list[$i]['country'])?$list[$i]['country']:null;
    // 上市时间
		$market_time = isset($list[$i]['market_time'])?$list[$i]['market_time']:null;
    // 指导价格
		$official_price = isset($list[$i]['official_price'])?$list[$i]['official_price']:null;
    // 购买地址
		$buy_url = isset($list[$i]['buy_url'])?$list[$i]['buy_url']:null;
    // 产品阶段
		$processed = isset($list[$i]['processed'])?$list[$i]['processed']:0;


		echo "set stuff[".$list[$i]['_id']."]..........\n";
		$total++;
	}
	if($max < $size){
		echo "stuff list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}



echo "stuff to product is OK! \n";

