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

    try{
      $c = new JdClient();
      $c->appKey = Doggy_Config::$vars['app.jos_api']['app_key'];
      $c->appSecret = Doggy_Config::$vars['app.jos_api']['app_secret'];
      //$c->version = '2.0';
      //$c->serverUrl = https://api.jd.com/routerjson;  // sdk已指定
      $req = new NewWareBaseproductGetRequest();
      $return_fields = array("valuePayFirst","saleUnit","model","phone","weight","issn","wserve","imagePath","skuMark","state",
                              "shopCategorys","brandId","isDelete","allnum","height","name","valueWeight","skuId","length","barCode",
                              "saleDate","safeDays","erpPid","cbrand","site","sizeSequence","productArea","packSpecification","width",
                              "cid2","maxPurchQty","ebrand","upc","url","size","category","venderType","color","shopName","pname",
                              "colorSequence","price",
      );

      $return_fields = implode(',', $return_fields);
      $req->setIds( $ids );
      $req->setBasefields( $return_fields );
      $resp = $c->execute($req, $c->accessToken);
      $resp = Sher_Core_Helper_Util::object_to_array($resp);
      if(empty($resp)){
        $result['msg'] = '请求无响应';
      }
      if($resp['code']==0){
        $result['success'] = true;
        $result['data'] = $resp;
      }
      //print_r($resp);exit;
      return $result;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('jd search by item error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  
  }
	
}

