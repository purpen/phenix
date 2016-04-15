<?php
/**
 * 极光推送SDK方法集
 * @author tianshuai
 */
class Sher_Core_Util_JPush {
    

  public function __construct() {
  }

  /**
   * 
   */
  public static function push($alert, $options=array()) {
    include "jpush-sdk/JPush.php";
    $app_key = Doggy_Config::$vars['app.jpush_api']['app_key'];
    $app_secret = Doggy_Config::$vars['app.jpush_api']['app_secret'];
    $log_path = Doggy_Config::$vars['app.jpush_api']['log_path'];
    $max_retry_times = Doggy_Config::$vars['app.jpush_api']['max_retry_times'];

    // ios
    $sound = isset($options['sound']) ? $options['sound'] : null;
    $badge = isset($options['badge']) ? $options['badge'] : null;
    $content_available = isset($options['content_available']) ? $options['content_available'] : false;
    $category = isset($options['category']) ? $options['category'] : null;

    // android
    $title = isset($options['title']) ? $options['title'] : null;
    $builderId = isset($options['builderId']) ? (int)$options['builderId'] : null;

    // win phone
    $_open_page = isset($options['_open_page']) ? $options['_open_page'] : null;

    // common
    $extras = isset($options['extras']) ? (array)$options['extras'] : array();
    $sendno = isset($options['sendno']) ? (int)$options['sendno'] : null;
    $time_to_live = isset($options['time_to_live']) ? (int)$options['time_to_live'] : 86400;  // 默认1天
    $override_msg_id = isset($options['override_msg_id']) ? (int)$options['override_msg_id'] : null;
    $apns_production = isset($options['apns_production']) ? $options['apns_production'] : false;
    $big_push_duration = isset($options['big_push_duration']) ? (int)$options['big_push_duration'] : null;

    try{
      $client = new JPush($app_key, $app_secret, $log_path, $max_retry_times);
      $push = $client->push();
      $push->setPlatform(array('ios'));

      $push->addIosNotification($alert, $sound, $badge, $content_available, $category, $extras);
      //$push->addAndroidNotification($alert, $title, $builderId, $extras);
      //$push->addWinPhoneNotification($alert, $title, $_open_page, $extras);

      $push->setOptions($sendno=null, $time_to_live=null, $override_msg_id=null, $apns_production=null, $big_push_duration=null);

    }catch(Exception $e){

    }

  }


	
}

