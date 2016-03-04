<?php
/**
 * 淘宝SDK方法集
 * @author tianshuai
 */
class Sher_Core_Util_TopSdk {
    

  public function __construct() {
  }

  /**
   * 商品搜索
   */
  public static function search($q, $options=array()) {
    include "taobao-sdk/TopSdk.php";
    $result = array();
    $result['success'] = false;
    $result['code'] = 0;
    $result['msg'] = null;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 1;
    // 城市
    $city = isset($options['city']) ? $options['city'] : null;
    $sort = isset($options['sort']) ? (int)$options['sort'] : 0;
    $page = isset($options['page']) ? (int)$options['page'] : 1;
    $size = isset($options['size']) ? (int)$options['size'] : 8; 

    if(empty($q)){
      $result['msg'] = '搜索关键字不能为空!';
      return $result;     
    }

    // 排序转换
    switch($sort){
      case 1: // 销量
        $sort_name = 'total_sales_des';
        break;
      case 2: // 佣金比例
        $sort_name = 'tk_rate_des';
        break;
      case 3: // 累计推广量
        $sort_name = 'tk_total_sales_des';
        break;
      case 4: // 支出佣金
        $sort_name = 'tk_total_commi_des';
        break;
      default:
        $sort_name = null;
    }

    try{
      $c = new TopClient;
      $c->appkey = Doggy_Config::$vars['app.tbk_api']['app_key'];
      $c->secretKey = Doggy_Config::$vars['app.tbk_api']['app_secret'];
      $c->format = 'json';
      $req = new TbkItemGetRequest;
      $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
      $req->setQ($q);
      //$req->setCat("16,18");

      if($city){
        $req->setItemloc($city);
      }
      if($sort_name){
        $req->setSort($sort_name);
      }
      //$req->setIsTmall("false");
      //$req->setIsOverseas("false");
      //$req->setStartPrice("10");
      //$req->setEndPrice("10");
      //$req->setStartTkRate("123");
      //$req->setEndTkRate("123");
      $req->setPlatform($platform);
      $req->setPageNo($page);
      $req->setPageSize($size);
      $resp = $c->execute($req);
      $resp = Sher_Core_Helper_Util::object_to_array($resp);
      if(isset($resp['results'])){
        $result['success'] = true;
        $result['msg'] = 'success';
        $result['data'] = $resp;  
      }elseif(isset($resp['code'])){
        Doggy_Log_Helper::warn('taobao search error:'.$resp['code'].': '.$resp['msg']);
        $result['msg'] = 'taobao search error:'.$resp['code'].': '.$resp['msg'];     
      }else{
        $result['msg'] = 'taobao search unknown error!';       
      }
      return $result;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('taobao search error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;
    }

  }

  /*
   * 单个商品查询(简版)
   */
  public static function search_by_item($ids, $options=array()){
    include "taobao-sdk/TopSdk.php";

    $result = array();
    $result['success'] = false;
    $result['code'] = 0;
    $result['msg'] = null;
    // 链接方式：1.PC; 2.无线
    $platform = isset($options['platform']) ? (int)$options['platform'] : 1;
    // 城市
    $ids = isset($options['ids']) ? $options['ids'] : null; 


    if(empty($ids)){
      $result['msg'] = 'id不能为空!';
      return $result;     
    }

    try{
      $c = new TopClient;
      $c->appkey = Doggy_Config::$vars['app.tbk_api']['app_key'];
      $c->secretKey = Doggy_Config::$vars['app.tbk_api']['app_secret'];
      $c->format = 'json';
      $req = new TbkItemInfoGetRequest;
      $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url");
      $req->setPlatform($platform);
      $req->setNumIids($ids);
      $resp = $c->execute($req);
      $resp = Sher_Core_Helper_Util::object_to_array($resp);

      if(isset($resp['results'])){
        $result['success'] = true;
        $result['msg'] = 'success';
        $result['data'] = $resp;  
      }elseif(isset($resp['code'])){
        Doggy_Log_Helper::warn('taobao search item error:'.$resp['code'].': '.$resp['msg']);
        $result['msg'] = 'taobao search item error:'.$resp['code'].': '.$resp['msg'];     
      }else{
        $result['msg'] = 'taobao search item unknown error!';       
      }

      return $result;

    }catch(Exception $e){
      Doggy_Log_Helper::warn('taobao search item error:'.$e->getMessage());
      $result['msg'] = $e->getMessage();
      return $result;   
    }

  
  }

	
}

