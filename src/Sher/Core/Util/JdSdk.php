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
    include "jos-sdk/JdSdk.php";
    $result = array();
    $result['success'] = false;
    $result['code'] = 0;

    $sort = isset($options['sort']) ? (int)$options['sort'] : 0;
    $page = isset($options['page']) ? (int)$options['page'] : 1;
    $size = isset($options['size']) ? (int)$options['size'] : 8; 

    if(empty($q)){
      $result['msg'] = '搜索关键字不能为空!';
      return $result;     
    }

    // 排序转换
    switch($sort){
      case 1: // 
        $sort_name = 'sort_dredisprice_asc';
        break;
      case 2: // 
        $sort_name = 'sort_dredisprice_desc';
        break;
      case 3: // 
        $sort_name = 'sort_redissale_desc';
        break;
      default:
        $sort_name = null;
    }

    try{

      $c = new JdClient();
      $c->appKey = Doggy_Config::$vars['app.jos_api']['app_key'];
      $c->appSecret = Doggy_Config::$vars['app.jos_api']['app_secret'];
      //$c->accessToken = accessToken;
      //$c->serverUrl = SERVER_URL;
      $req = new WaresSearchRequest();
      $req->setTitle($q);

      if($sort_name){
        $req->setSortType($sort_name);
      }
      $req->setPagesize($size);
      $req->setPage($page);
      /**
      $req->setFiltType();
      $req->setAreaIds();
      $req->setExtAttr( "jingdong" );
      $req->setBrandCol( "jingdong" );
      $req->setPriceCol( "jingdong" );
      $req->setMergeSku( "jingdong" );
      $req->setMultiSuppliers( "jingdong" );
      $req->setShopCol( "jingdong" );
      $req->setColorCol( "jingdong" );
      $req->setSizeCol( "jingdong" );
      $req->setLocationId( "jingdong" );
      $req->setNp( "jingdong" );
      $req->setExtAttrSort( "jingdong" );
      $req->setExtRes( "jingdong" );
      $req->setCod( "jingdong" );
      */

      $resp = $c->execute($req, $c->accessToken);

      print_r($resp);exit;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('jd search error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  }

  /*
   * 单个商品查询
   */
  public static function search_by_item($ids, $options=array()){
    include "jos-sdk/JdSdk.php";

    $result = array();
    $result['success'] = false;
    $result['code'] = 0;
    $result['msg'] = null;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 2;

    // id ** 最大40个(淘宝)
    if(empty($ids)){
      $result['msg'] = 'id不能为空!';
      return $result;     
    }

    return $result;   
  }
	
}

