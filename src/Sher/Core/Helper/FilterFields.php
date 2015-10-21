<?php
/**
 * 过滤字段
 * @author tianshuai
 *
 * @package default
 */
class Sher_Core_Helper_FilterFields {

  /**
   * 过滤列表页用户输入
   */
  public static function user_list($user, $options=array()) {
    if(empty($user)){
      return false;
    }

    $filter = array();

    $filter_arr = array('_id', 'nickname', 'home_url', 'small_avatar_url', 'symbol', 'mini_avatar_url');

    $some_fields = array_merge($filter_arr, $options);

    foreach($some_fields as $k){
      $filter[$k] = isset($user[$k]) ? $user[$k] : null;
    }

    return $filter;
    
	}

  /**
   * 过滤多余字段
   * type=1, 允许出现的字段； type=2，不允许出现的字段
   */
  public static function filter_fields($result, $options=array(), $type=1){
    if(empty($result)) return array();
    if(empty($options)) return $result;
    $data = array();
    $count = count($result);
    for($i=0;$i<$count;$i++){
      foreach($options as $v){
        if($type==1){
          $data[$i][$v] = isset($result[$i][$v]) ? $result[$i][$v] : null;
        }elseif($type==2){
          unset($result[$i][$v]);
        }
      }
    }
    if($type==1){
      return $data;
    }elseif($type==2){
      return $result;
    }
  }

}
