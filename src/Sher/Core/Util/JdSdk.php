<?php
/**
 * 京东skd
 * @author tianshuai
 */
class Sher_Core_Util_JdSdk {
    

  public function __construct() {
  }

  /**
   * 商品搜索
   */
  public static function search($q, $options=array()) {
    $result = array();
    $result['success'] = false;
    $result['code'] = 0;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 1;
    $sort = isset($options['sort']) ? (int)$options['sort'] : 0;
    $page = isset($options['page']) ? (int)$options['page'] : 1;
    $size = isset($options['size']) ? (int)$options['size'] : 8; 

    if(empty($q)){
      $result['msg'] = '搜索关键字不能为空!';
      return $result;     
    }

    try{


    }catch(Exception $e){
      Doggy_Log_Helper::warn('jd search error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  }
	
}

