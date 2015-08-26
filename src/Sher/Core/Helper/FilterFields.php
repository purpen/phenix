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

    $filter_arr = array('_id', 'nickname', 'home_url', 'small_avatar_url', 'symbol', 'mini_avatar_url' );

    $some_fields = array_merge($filter_arr, $options);

    foreach($some_fields as $k){
      $filter[$k] = isset($user[$k]) ? $user[$k] : null;
    }

    return $filter;
    
	}

}
