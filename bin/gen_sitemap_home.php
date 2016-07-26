#!/usr/bin/env php
<?php
/**
 * 生成网站地图sitemap.xml --home
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
date_default_timezone_set('Asia/shanghai');

echo "gen sitemap topic xml ...\n";

$sitemap_model = new Sher_Core_Util_Sitemap(false);
$sitemap_model->page('home');
$total = 0;
$domain_base = Doggy_Config::$vars['app.domain.base'];
$urls = array(
  array($domain_base, '首页', '--', 1),
  array($domain_base.'/shop', '商品首页', '--', 2),
  array($domain_base.'/topic', '话题首页', '--', 3),
  array($domain_base.'/incubator', '孵化首页', '--', 4),
  array($domain_base.'/active', '活动首页', '--', 5),
  array($domain_base.'/try', '试用首页', '--', 6),
  array($domain_base.'/d3in', '实验室首页', '--', 7),
  array($domain_base.'/search', '搜索', '--', 8),

  // 商品
  array($domain_base.'/shop/c0', '商品', '全部', 2),
  array($domain_base.'/shop/c82', '商品', '新奇特', 2),
  array($domain_base.'/shop/c80', '商品', '宠物', 2),
  array($domain_base.'/shop/c81', '商品', '创意产品', 2),
  array($domain_base.'/shop/c79', '商品', '智能情趣', 2),
  array($domain_base.'/shop/c78', '商品', '智能母婴', 2),
  array($domain_base.'/shop/c76', '商品', '智能手环', 2),
  array($domain_base.'/shop/c77', '商品', '智能手表', 2),
  array($domain_base.'/shop/c30', '商品', '运动健康', 2),
  array($domain_base.'/shop/c31', '商品', '数码电子', 2),
  array($domain_base.'/shop/c32', '商品', '智能家具', 2),
  array($domain_base.'/shop/c33', '商品', '娱乐生活', 2),
  array($domain_base.'/shop/c34', '商品', '户外休闲', 2),

  // 话题
  array($domain_base.'/shop/c59', '话题', '智创学堂', 3),
  array($domain_base.'/shop/c61', '话题', '孵化需求', 3),
  array($domain_base.'/shop/c15', '话题', '活动动态', 3),
  array($domain_base.'/shop/c21', '话题', '品牌专区', 3),
  array($domain_base.'/shop/c18', '话题', '产品评测', 3),
  array($domain_base.'/shop/c27', '话题', '自由讨论', 3),

  // 关于
  array($domain_base.'/guide/about', '关于我们', '--', 1),
  array($domain_base.'/guide/media', '媒体报导', '--', 1),
  array($domain_base.'/guide/contact', '联系我们', '--', 1),
  array($domain_base.'/guide/succase', '成功案例', '--', 1),
  array($domain_base.'/guide/join', '加入我们', '--', 1),
  array($domain_base.'/helper/link', '友情链接', '--', 1),



);

for ($i=0; $i < count($urls); $i++) {
  $time = date('Y-m-d');
  $url = $urls[$i][0];
  $sitemap_model->url($url, $time, 'weekly', '1.0');
  $total++;
}

$sitemap_model->close();

echo "gen sitemap home num: $total is OK! \n";

