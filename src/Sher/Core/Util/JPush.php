<?php
/**
 * 极光推送SDK方法集
 * @author tianshuai
 */
class Sher_Core_Util_JPush {
    

  public function __construct() {
  }

  /**
   * 推送
   */
  public static function push($alert, $options=array()) {
    include "jpush-sdk/JPush.php";
    $app_key = Doggy_Config::$vars['app.jpush_api']['app_key'];
    $app_secret = Doggy_Config::$vars['app.jpush_api']['app_secret'];
    $log_path = !empty(Doggy_Config::$vars['app.jpush_api']['log_path']) ? Doggy_Config::$vars['app.jpush_api']['log_path'] : null;
    $max_retry_times = Doggy_Config::$vars['app.jpush_api']['max_retry_times'];

    // ios
    $sound = isset($options['sound']) ? $options['sound'] : 'default';
    $badge = isset($options['badge']) ? $options['badge'] : null;
    $content_available = isset($options['content_available']) ? $options['content_available'] : false;
    $category = isset($options['category']) ? $options['category'] : null;

    // android
    $title = isset($options['title']) ? $options['title'] : null;
    $builderId = isset($options['builderId']) ? (int)$options['builderId'] : null;

    // win phone
    $_open_page = isset($options['_open_page']) ? $options['_open_page'] : null;

    // common
    $plat_form = isset($options['plat_form']) ? (array)$options['plat_form'] : array();
    // 广播推送
    $all_audience = isset($options['all_audience']) ? $options['all_audience'] : false;
    $tags = isset($options['tags']) ? (array)$options['tags'] : array();
    $alias = isset($options['alias']) ? (array)$options['alias'] : array();
    $extras = isset($options['extras']) ? (array)$options['extras'] : array();
    $sendno = isset($options['sendno']) ? (int)$options['sendno'] : null;
    $time_to_live = isset($options['time_to_live']) ? (int)$options['time_to_live'] : 86400;  // 默认1天
    $override_msg_id = isset($options['override_msg_id']) ? (int)$options['override_msg_id'] : null;
    $apns_production = isset($options['apns_production']) ? $options['apns_production'] : false;
    $big_push_duration = isset($options['big_push_duration']) ? (int)$options['big_push_duration'] : null;


    try{
      $client = new JPush($app_key, $app_secret, $log_path, $max_retry_times);
      $push = $client->push();
      $push->setPlatform($plat_form);

      // 广播推送
      if($all_audience){
        if(empty($tags) && empty($alias)){
          $push->addAllAudience();
        }     
      }

      if(!empty($tags)){
        $push->addTags($tags); 
      }
      if(!empty($alias)){
        $push->addAlias($alias); 
      }

      if(in_array('ios', $plat_form)){
        $push->addIosNotification($alert, $sound, $badge, $content_available, $category, $extras);
      }
      if(in_array('android', $plat_form)){
        $push->addAndroidNotification($alert, $title, $builderId, $extras);
      }
      // win phone
      //$push->addWinPhoneNotification($alert, $title, $_open_page, $extras);

      $push->setOptions($sendno, $time_to_live, $override_msg_id, $apns_production, $big_push_duration);

      // 获取构建对象
      $push->build();
      // 打印到控制台
      //$push->printJSON();
      // 推送
      $ok = $push->send();
      $data = array(
        'sendno' => $ok->data->sendno,
        'msg_id'  =>  $ok->data->msg_id,
        'rateLimitLimit' =>  $ok->limit->rateLimitLimit,
        'rateLimitRemaining' =>  $ok->limit->rateLimitRemaining,
        'rateLimitReset' =>  $ok->limit->rateLimitReset,
      );
      return array('success'=>true, 'message'=>'ok', 'data'=>$data);

    }catch(Exception $e){
      return array('success'=>false, 'message'=>$e->getMessage());
    }

  }


	
}

