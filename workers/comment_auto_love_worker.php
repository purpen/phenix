<?php
/**
 * 评论自动点赞(用小号)
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
echo "===============COMMENT AUTO LOVE WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "begin comment auto add love ...\n";

$comment_model = new Sher_Core_Model_Comment();

// 获取试用名单---取块内容
$comment_items = Sher_Core_Util_View::load_block('auto_comment_love_gen', 1);

if(empty($comment_items)){
  echo "block comment_ids is empty! \n";
  // sleep 10 minute
  sleep(600);
  exit(0);
}
$comment_arr = explode(';',$comment_items);

foreach($comment_arr as $k=>$v){
  if(empty($v)){
    continue;
  }
  $v_arr = explode('|', $v);
  if(empty($v_arr) || count($v_arr)<4){
    echo "block format is wrong!! \n";
    continue;
  }
  $comment_id = (string)$v_arr[0];
  $switch = (int)$v_arr[1];
  $page = (int)$v_arr[2];
  $max_count = (int)$v_arr[3];
  $mark = sprintf("user_list_0%d", $page);

  if($switch==0){
    echo "comment_id: $comment_id is close! next.... \n";
    continue; 
  }

  try{
    $comment = $comment_model->load($comment_id);
    if(empty($comment)){
      echo "comment: $comment_id not exist model!\n";
      continue;
    }

    if($max_count < $comment['love_count']){
      echo "comment: $comment_id is max love count!\n";
      continue;
    }

    $user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $page);
    if(empty($user_list_arr)){
      echo "user list is empty!\n";
      continue;
    }

    $user_index = array_rand($user_list_arr, 1);
    $user_id = (int)$user_list_arr[$user_index];
    if(empty($user_id)){
      echo "user is null! \n";
      continue;
    }

    // 开始点赞
    // 检测是否已提交过申请
    $fav_model = new Sher_Core_Model_Favorite();

    if ($fav_model->check_loved($user_id, $comment_id, Sher_Core_Model_Favorite::TYPE_COMMENT)) {
      echo "user id: $user_id has loved! \n";
      // 删除块用户
      Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
      continue;
    }

    $fav_info = array(
      'type' => Sher_Core_Model_Favorite::TYPE_COMMENT,
    );
    $ok = $fav_model->add_love($user_id, $comment_id, $fav_info);
    if($ok){
      // 获取计数
      $comment = $comment_model->find_by_id($comment_id);
      if($comment){
        if(isset($comment['invented_love_count'])){
          $comment_model->inc_counter('invented_love_count', 1, $comment_id);
        }else{
          $comment_model->update_set($comment_id, array('invented_love_count'=>1));
        }
      }
      echo "comment love is success!!\n";
      // 删除块用户
      Sher_Core_Util_View::remove_part_content($mark, $user_id, ',');
    }else{
      echo "comment love is faile! \n";
    }
      
  }catch(Sher_Core_Model_Exception $e){
    echo "find comment_model failed: ".$e->getMessage();
    continue;
  }

} // for end

echo "===========================COMMENT LOVE WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

$hr = date('G');
if($hr >= 9 && $hr <= 23){
    $time = rand(60, 300);
}else{
    $time = rand(600, 900);
}
// sleep N minute
sleep($time);
exit(0);
