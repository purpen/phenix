#!/usr/bin/env php
<?php
/**
 * 生成网站地图sitemap.xml --所有列表
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

$type_list = array(
  'topic',
  'product',
  'active',
  'try',
);

for($t=0; $t<count($type_list); $t++){
  $type_mode = $type_list[$t];
  gen_mode_list($type_mode);
}


function gen_mode_list($type_mode){
  $sitemap_model = new Sher_Core_Util_Sitemap(false);
  echo "gen sitemap $type_mode xml ...\n";

  switch($type_mode){
  case 'topic':
    $model = new Sher_Core_Model_Topic();
    break;
  case 'product':
    $model = new Sher_Core_Model_Product();
    break;
  case 'active':
    $model = new Sher_Core_Model_Active();
    break;
  case 'try':
    $model = new Sher_Core_Model_Try();
    break;
  default:
    $model = null;

  }

  $sitemap_model->page($type_mode);
  $page = 1;
  $size = 1000;
  $is_end = false;
  $total = 0;
  while(!$is_end){
    $query = array();
    $fields = array();

    switch($type_mode){
    case 'topic':
      //$query['published'] = 1;
      $fields = array('_id', 'published', 'created_on', 'updated_on');
      break;
    case 'product':
      $query['published'] = 1;
      $fields = array('_id', 'published', 'stage', 'created_on', 'updated_on');
      break;
    case 'active':
      $query['state'] = 1;
      $fields = array('_id', 'state', 'created_on', 'updated_on');
      break;
    case 'try':
      $query['state'] = 1;
      $fields = array('_id', 'state', 'created_on', 'updated_on');
      break;
    default:
      $query = array();
      $fields = array();
    }

    $options = array('field' => $fields,'page'=>$page,'size'=>$size,'sort'=>array('_id'=>-1));

    $list = $model->find($query, $options);
    if(empty($list)){
      echo "get $type_mode list is null,exit......\n";
      break;
    }
    $max = count($list);
    for ($i=0; $i < $max; $i++) {
      $id = $list[$i]['_id'];
      $time = date('Y-m-d', $list[$i]['updated_on']);
      
      switch($type_mode){
      case 'topic':
        $url = Sher_Core_Helper_Url::topic_view_url($id);
        break;
      case 'product':
        $url = $model->gen_view_url(array('_id'=>$id, 'stage'=>$list[$i]['stage']));
        break;
      case 'active':
        $url = Sher_Core_Helper_Url::active_view_url($id);
        break;
      case 'try':
        $url = sprintf(Doggy_Config::$vars['app.url.try.view'], $id);
        break;
      default:
        $url = null;
      }

      $sitemap_model->url($url, $time, 'weekly', '0.9');
      $total++;
    }
    if($max < $size){
      break;
    }
    $page++;
    echo "page [$page] is generate..---------\n";
  }

  $sitemap_model->close();

  echo "gen sitemap $type_mode num: $total is OK! \n";

  unset($model);
  unset($sitemap_model);

}


