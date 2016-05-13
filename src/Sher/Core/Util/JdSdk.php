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

    if(empty($ids)){
      $result['msg'] = '缺少请求参数';
      return $result;    
    }

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
        return $result;
      }
      if($resp['code']==0 && isset($resp['listproductbase_result'])){

        $req1 = new WarePriceGetRequest();
        $req2 = new WareProductimageGetRequest();
        foreach($resp['listproductbase_result'] as $k=>$v){
          $sku_id = sprintf("J_%s", $v['skuId']);
          $req1->setSkuId( $sku_id );
          $req2->setSkuId($v['skuId']);
          $resp1 = $c->execute($req1, $c->accessToken);
          $resp2 = $c->execute($req2, $c->accessToken);
          $resp1 = Sher_Core_Helper_Util::object_to_array($resp1);
          $resp2 = Sher_Core_Helper_Util::object_to_array($resp2);

          // 获取价格
          if($resp1['code']==0 && isset($resp1['price_changes'])){
            $resp['listproductbase_result'][$k]['sale_price'] = $resp1['price_changes'][0]['price'];
            $resp['listproductbase_result'][$k]['market_price'] = $resp1['price_changes'][0]['market_price'];
          }else{
            $resp['listproductbase_result'][$k]['sale_price'] = null;
            $resp['listproductbase_result'][$k]['market_price'] = null;
          }
          // 获取图片组
          if($resp2['code']==0 && isset($resp2['image_path_list'])){
            $urls = array();
            for($i=0;$i<count($resp2['image_path_list'][0]['image_list']);$i++){
              $url = isset($resp2['image_path_list'][0]['image_list'][$i]['path']) ? $resp2['image_path_list'][0]['image_list'][$i]['path'] : null;
              if($url){
                $url = trim(str_replace('/n5/', '/n0/', $url));
                array_push($urls, $url);
              }
            }
            $resp['listproductbase_result'][$k]['banners_url'] = $urls;
          }else{
            $resp['listproductbase_result'][$k]['banners_url'] = array();
          }
          
        } // end foreach
        $result['success'] = true;
        $result['data'] = $resp;
      }else{
        $result['code'] = $resp['code'];
      }
      //print_r($resp);exit;
      return $result;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('jd search by item error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  }

  /*
   * 单个商品价格查询
   */
  public static function search_by_item_price($sku_id, $options=array()){
    include "jos-sdk/JdSdk.php";

    $result = array();
    $result['success'] = false;
    $result['code'] = 0;
    $result['msg'] = null;

    if(empty($sku_id)){
      $result['msg'] = '缺少请求参数';
      return $result;    
    }

    try{
      $c = new JdClient();
      $c->appKey = Doggy_Config::$vars['app.jos_api']['app_key'];
      $c->appSecret = Doggy_Config::$vars['app.jos_api']['app_secret'];

      $req = new WarePriceGetRequest();
      $sku_id = sprintf("J_%s", $sku_id);
      $req->setSkuId( $sku_id );
      $resp = $c->execute($req, $c->accessToken);
      $resp = Sher_Core_Helper_Util::object_to_array($resp);
      if(empty($resp)){
        $result['msg'] = '请求无响应';
        return $result;
      }
      if($resp['code']==0 && isset($resp['price_changes'])){
        $result['success'] = true;
        $result['data'] = $resp;
      }else{
        $result['code'] = $resp['code'];
      }
      //print_r($resp);exit;
      return $result;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('jd fetch item price error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  }
	
}

