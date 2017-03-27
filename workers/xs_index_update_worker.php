<?php
/**
 * 定时更新全文索引---迅搜
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
@require 'autoload.php';
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============INDEX XunSearch WORKER UPDATE WAKE UP===============\n";
echo "-------------------------------------------------\n";

// 获取要更新的对象ID数组
$digged = new Sher_Core_Model_DigList();

//获取话题更新的ID数组
$topic_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS);
if(!empty($topic_ids) && !empty($topic_ids['items'])){
  echo "Prepare to update topic fulltext index...\n";
  $topic_mode = new Sher_Core_Model_Topic();
  $total = 0;
  $item = null;
  foreach($topic_ids['items'] as $k=>$v){
    $item = $topic_mode->load((int)$v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS, (int)$v); 
      continue;
    }
    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Topic');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'topic_'.(string)$item['_id'],
      'kind' => 'Topic',
      'oid' => $item['_id'],
      'cid' => 1,
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['description'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update topic id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_FAIL_IDS, $item['_id']);
      echo "fail update topic id: $v $result[msg]";
    }

  }//endfor

  echo "success update topic $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取商品更新的ID数组
$product_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS);
if(!empty($product_ids) && !empty($product_ids['items'])){
  echo "Prepare to update product fulltext index...\n";
  $product_mode = new Sher_Core_Model_Product();
  $total = 0;
  $item = null;
  foreach($product_ids['items'] as $k=>$v){
    $item = $product_mode->load((int)$v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS, (int)$v); 
      continue;
    }

    $stage = $item['stage'];
    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Product');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'product_'.(string)$item['_id'],
      'kind' => 'Product',
      'oid' => $item['_id'],
      'cid' => $stage,
      'tid' => $item['brand_id'],
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['content'])),
      'desc'  =>$item['advantage'],
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update product id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_FAIL_IDS, $item['_id']);
      echo "fail update product id: $v $result[msg]";
    }

  }//endfor

  echo "success update product $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取灵感更新的ID数组
$stuff_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS);
if(!empty($stuff_ids) && !empty($stuff_ids['items'])){
  echo "Prepare to update stuff fulltext index...\n";
  $stuff_mode = new Sher_Core_Model_Stuff();
  $total = 0;
  $item = null;
  foreach($stuff_ids['items'] as $k=>$v){
    $item = $stuff_mode->load((int)$v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS, (int)$v);
      continue;
    }
    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Stuff');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'stuff_'.(string)$item['_id'],
      'kind' => 'Stuff',
      'oid' => $item['_id'],
      'cid' => isset($item['from_to'])?$item['from_to']:0,
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['description'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update stuff id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_FAIL_IDS, $item['_id']);
      echo "fail update stuff id: $v $result[msg]";
    }

  }//endfor

  echo "success update stuff $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


/*
//获取地盘更新的ID数组
$scene_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_UPDATE_IDS);
if(!empty($scene_ids) && !empty($scene_ids['items'])){
  echo "Prepare to update scene fulltext index...\n";
  $scene_model = new Sher_Core_Model_SceneScene();
  $total = 0;
  $item = null;
  foreach($scene_ids['items'] as $k=>$v){
    $item = $scene_model->load((int)$v);
    if(empty($item) || $item['is_check']==0 || $item['deleted']==1){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_UPDATE_IDS, (int)$v);
      // 删除索引
      Sher_Core_Util_XunSearch::del_ids('scene_'.(string)$v);
      continue;
    }

    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Scene');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'scene_'.(string)$item['_id'],
      'kind' => 'Scene',
      'oid' => $item['_id'],
      'cid' => $item['category_id'],
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['des'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update scene id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_FAIL_IDS, $item['_id']);
      echo "fail update scene id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene $total .\n";
  echo "-------------//////////////-------------\n";
}//endif

 */


//获取情境更新的ID数组
$sight_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SIGHT_UPDATE_IDS);
if(!empty($sight_ids) && !empty($sight_ids['items'])){
  echo "Prepare to update scence sight fulltext index...\n";
  $scene_sight_model = new Sher_Core_Model_SceneSight();
  $total = 0;
  $item = null;
  foreach($sight_ids['items'] as $k=>$v){
    $item = $scene_sight_model->load((int)$v);
    if(empty($item) || $item['is_check']==0 || $item['deleted']==1){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SIGHT_UPDATE_IDS, (int)$v); 
      // 删除索引
      Sher_Core_Util_XunSearch::del_ids('sight_'.$v);
      continue;
    }
    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'Sight');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'sight_'.(string)$item['_id'],
      'kind' => 'Sight',
      'oid' => $item['_id'],
      'cid' => 0,
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['des'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SIGHT_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update scene sight id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SIGHT_FAIL_IDS, $item['_id']);
      echo "fail update scene sight id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene sight $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


/**
//获取情境产品更新的ID数组
$scene_product_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_PRODUCT_UPDATE_IDS);
if(!empty($scene_product_ids) && !empty($scene_product_ids['items'])){
  echo "Prepare to update scence sight fulltext index...\n";
 $scene_product_model = new Sher_Core_Model_SceneProduct();
  $total = 0;
  $item = null;
  foreach($scene_product_ids['items'] as $k=>$v){
    $item = $scene_product_model->load((int)$v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_PRODUCT_UPDATE_IDS, (int)$v); 
      continue;
    }
    //获取封面图
    if($item['cover_id']){
      $cover_id = $item['cover_id'];
    }else{
      $cover = Sher_Core_Helper_Search::fetch_asset($item['_id'], 'SProduct');
      if(!empty($cover)){
        $cover_id = $cover['_id'];
      }else{
        $cover_id = '';
      }
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'scene_product_'.(string)$item['_id'],
      'kind' => 'SProduct',
      'oid' => $item['_id'],
      'cid' => $item['oid'],
      'title' => $item['title'],
      'cover_id' => $cover_id,
      'content' => strip_tags(htmlspecialchars_decode($item['summary'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_PRODUCT_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update scene product id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_PRODUCT_FAIL_IDS, $item['_id']);
      echo "fail update scene product id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene product $total .\n";
  echo "-------------//////////////-------------\n";
}//endif
 */

//获取情境分享语句更新的ID数组
$scene_context_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_CONTEXT_UPDATE_IDS);
if(!empty($scene_context_ids) && !empty($scene_context_ids['items'])){
  echo "Prepare to update scence sight fulltext index...\n";
  $scene_context_model = new Sher_Core_Model_SceneContext();
  $total = 0;
  $item = null;
  foreach($scene_context_ids['items'] as $k=>$v){
    $item = $scene_context_model->load($v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_CONTEXT_UPDATE_IDS, $v); 
      continue;
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'scene_context_'.(string)$item['_id'],
      'kind' => 'SContext',
      'oid' => (string)$item['_id'],
      'cid' => $item['category_id'],
      'title' => $item['title'],
      'cover_id' => '',
      'content' => strip_tags(htmlspecialchars_decode($item['des'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_CONTEXT_UPDATE_IDS, (string)$item['_id']); 
      $total++;
      echo "success update scene context id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_CONTEXT_FAIL_IDS, (string)$item['_id']);
      echo "fail update scene context id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene context $total .\n";
  echo "-------------//////////////-------------\n";
}//endif

//获取情境主题更新的ID数组
$scene_subject_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_SUBJECT_UPDATE_IDS);
if(!empty($scene_subject_ids) && !empty($scene_subject_ids['items'])){
  echo "Prepare to update scence subject fulltext index...\n";
  $scene_subject_model = new Sher_Core_Model_SceneSubject();
  $total = 0;
  $item = null;
  foreach($scene_subject_ids['items'] as $k=>$v){
    $item = $scene_subject_model->load($v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_SUBJECT_UPDATE_IDS, $v); 
      continue;
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'scene_subject_'.(string)$item['_id'],
      'kind' => 'SSubject',
      'oid' => (string)$item['_id'],
      'cid' => $item['type'],
      'title' => $item['title'],
      'cover_id' => $item['cover_id'],
      'content' => strip_tags(htmlspecialchars_decode($item['summary'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_SUBJECT_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update scene subject id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_SUBJECT_FAIL_IDS, $item['_id']);
      echo "fail update scene subject id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene subject $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取情境品牌更新的ID数组
$scene_brand_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_BRAND_UPDATE_IDS);
if(!empty($scene_brand_ids) && !empty($scene_brand_ids['items'])){
  echo "Prepare to update scence brand fulltext index...\n";
  $scene_brand_model = new Sher_Core_Model_SceneBrands();
  $total = 0;
  $item = null;
  foreach($scene_brand_ids['items'] as $k=>$v){
    $item = $scene_brand_model->load($v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_BRAND_UPDATE_IDS, $v); 
      continue;
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'scene_brand_'.(string)$item['_id'],
      'kind' => 'SBrand',
      'oid' => (string)$item['_id'],
      'cid' => $item['from_to'],
      'title' => $item['title'],
      'cover_id' => $item['cover_id'],
      'content' => strip_tags(htmlspecialchars_decode($item['des'])),
      'user_id' => $item['user_id'],
      'tags' => !empty($item['tags']) ? implode(',', $item['tags']) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_BRAND_UPDATE_IDS, (string)$item['_id']); 
      $total++;
      echo "success update scene brand id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_SCENE_BRAND_FAIL_IDS, (string)$item['_id']);
      echo "fail update scene brand id: $v $result[msg]";
    }

  }//endfor

  echo "success update scene brand $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


//获取用户更新的ID数组
$user_ids = $digged->load(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_USER_UPDATE_IDS);
if(!empty($user_ids) && !empty($user_ids['items'])){
  echo "Prepare to update user fulltext index...\n";
  $user_model = new Sher_Core_Model_User();
  $total = 0;
  $item = null;
  foreach($user_ids['items'] as $k=>$v){
    $item = $user_model->load((int)$v);
    if(empty($item)){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_USER_UPDATE_IDS, $v); 
      continue;
    }

    $nickname = (int)$item['nickname'];
    if(strlen($nickname)==11){  // 如果是手机号，不参与索引，跳过
        //删除Dig相应ID
        $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_USER_UPDATE_IDS, $v); 
        continue;
    }
    $user_tags = isset($item['tags']) ? $item['tags'] : array();
    if(!is_array($user_tags)) $user_tags = array();
    if(isset($item['profile']['label']) && !empty($item['profile']['label'])){
        array_push($user_tags, $item['profile']['label']);
    }
    if(isset($item['profile']['expert_label']) && !empty($item['profile']['expert_label'])){
        array_push($user_tags, $item['profile']['expert_label']);
    }

    //添加全文索引
    $xs_data = array(
      'pid' => 'user_'.(string)$item['_id'],
      'kind' => 'User',
      'oid' => (string)$item['_id'],
      'cid' => $item['from_site'],
      'title' => $item['nickname'],
      'cover_id' => '',
      'content' => strip_tags(htmlspecialchars_decode($item['summary'])),
      'user_id' => $item['_id'],
      'tags' => !empty($user_tags) ? implode(',', $user_tags) : '',
      'created_on' => $item['created_on'],
      'updated_on' => $item['updated_on'],
    );
    
    $result = Sher_Core_Util_XunSearch::update($xs_data);
    if($result['success']){
      //删除Dig相应ID
      $digged->remove_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_USER_UPDATE_IDS, $item['_id']); 
      $total++;
      echo "success update user id $v .\n";
    }else{
      //记录失败ids
      $digged->add_item_custom(Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_USER_FAIL_IDS, $item['_id']);
      echo "fail update user id: $v $result[msg]";
    }

  }//endfor

  echo "success update user $total .\n";
  echo "-------------//////////////-------------\n";
}//endif


echo "All index update works done.\n";
echo "===========================INDEX XunSearch UPDATE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep 5 minute
sleep(300);
exit(0);
