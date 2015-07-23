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

$stuff_model = new Sher_Core_Model_Stuff();
$product_model = new Sher_Core_Model_Product();
$page = 1;
$size = 200;
$is_end = false;
$total = 0;
$fail_total = 0;
while(!$is_end){
  $fid = Doggy_Config::$vars['app.topic.idea_category_id'];
	$query = array('fid'=>(int)$fid);
	$options = array('page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $has_product = $product_model->first(array('old_stuff_id'=>$id));
    if(!empty($has_product)){
      continue;
    }
    $data = array();

    // 整理分类
    $category_id = z_category($list[$i]['category_id']);

    $data['old_stuff_id'] = $id;
    $data['user_id'] = $list[$i]['user_id'];
    $data['title'] = $list[$i]['title'];
    $data['category_id'] = $category_id;
    $data['short_title'] = isset($list[$i]['short_title'])?$list[$i]['short_title']:null;
    $data['content'] = isset($list[$i]['description'])?$list[$i]['description']:null;
    $data['tags'] = isset($list[$i]['tags'])?$list[$i]['tags']:array();
    $data['like_tags'] = isset($list[$i]['like_tags'])?$list[$i]['like_tags']:array();
    $data['cover_id'] = isset($list[$i]['cover_id'])?$list[$i]['cover_id']:null;

    $data['view_count'] = $list[$i]['view_count'];
    $data['favorite_count'] = $list[$i]['favorite_count'];
    $data['love_count'] = $list[$i]['love_count'];
    $data['asset_count'] = $list[$i]['asset_count'];
    // 虚拟喜欢数
    $data['invented_love_count'] = isset($list[$i]['invented_love_count'])?$list[$i]['invented_love_count']:0;
    $data['comment_count'] = $list[$i]['comment_count'];
    // 最后赞用户列表
    $data['last_love_users'] = isset($list[$i]['last_love_users'])?$list[$i]['last_love_users']:array();
    $data['published'] = $list[$i]['published'];
    // 推荐 0 默认; 1 编辑推荐; 2 推荐到首页
    $data['stick'] = isset($list[$i]['stick'])?$list[$i]['stick']:0;
    // 精选
    $data['featured'] = isset($list[$i]['featured'])?$list[$i]['featured']:0;
    $data['random'] = isset($list[$i]['random'])?$list[$i]['random']:0;

    // 关联产品
    $data['fever_id'] = isset($list[$i]['fever_id'])?$list[$i]['fever_id']:0;
    // 是否审核
    $data['approved'] = isset($list[$i]['verified'])?$list[$i]['verified']:1;
    // 品牌ID
    $data['cooperate_id'] = isset($list[$i]['cooperate_id'])?$list[$i]['cooperate_id']:null;

    // 团队介绍
    $team_introduce = isset($list[$i]['team_introduce'])?$list[$i]['team_introduce']:null;
    // 品牌名称
    $brand = isset($list[$i]['brand'])?$list[$i]['brand']:null;
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

    $product_info = array(
      'team_introduce' => $team_introduce,
      'brand' => $brand,
      'designer' => $designer,
      'country' => $country,
      'market_time' => $market_time,
      'official_price' => $official_price,
      'buy_url' => $buy_url,
      'processed' => $processed,
    );
    $data['product_info'] = $product_info;

    $data['asset'] = array();

    $data['stage'] = Sher_Core_Model_Product::STAGE_IDEA;

    $ok = $product_model->create($data);
    if($ok){

 		  echo "create product[".$product_model->id."].is OK!.........\n";
		  $total++;   
    }else{
      echo "create product fail! id: $id \n";
      $fail_total++;
    }

	}
	if($max < $size){
		echo "stuff list is end!!!!!!!!!,exit.\n";
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}


echo "stuff to product is OK! count:$total \n";
echo "stuff to product is Fail! count:$fail_total \n";


// 整理分类
function z_category($cate){
  switch((int)$cate){
  case 36:  //智能手环
    $new_cate = 76;
    break;
  case 37:  //智能手表
    $new_cate = 77;
    break;
  case 52:  //健康监测
    $new_cate = 30;
    break;
  case 53:  //智能家具
    $new_cate = 32;
    break;
  case 55:  //智能首饰
    $new_cate = 30;
    break;
  case 56:  //智能母婴
    $new_cate = 78;
    break;
  case 57:  //创意产品
    $new_cate = 81;
    break;
  case 54:  //新奇特
    $new_cate = 82;
    break;
  default:
    $new_cate = 0;
  }
  return $new_cate;
}
