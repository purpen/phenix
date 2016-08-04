<?php
/**
 * 队列任务
 * @author tianshuai
 */
class Sher_Core_Util_Resque extends Doggy_Object {

  /**
   * 发起异步队列任务
   */
  public static function queue($name, $obj_name, $options=array()){
        // 设置发送任务
        Resque::setBackend(Doggy_Config::$vars['app.redis_host']);

        $verified = (int)Doggy_Config::$vars['app.redis.default']['verified'];
        if(!empty($verified)){
            $ret = Resque::redis()->auth(Doggy_Config::$vars['app.redis.default']['requirepass']);
          if ($ret === false) {
            die($redis->getLastError());
          }   
        }
		return Resque::enqueue($name, $obj_name, $options);
  }



}
