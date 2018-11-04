<?php
/**
 * 公众号开发
 * @author tianshuai
 */
class Sher_Core_Util_WxPub extends Doggy_Object {

	/**
	 * 用SHA1算法生成安全签名
	 * @param string $token 票据
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 * @param string $encrypt 密文消息
	 */
	public static function checkSignature($token, $timestamp, $nonce, $signature)
	{
		//排序
		try {
			$array = array($token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			$str = implode($array);
      if (sha1($str) != $signature) {
        return false;
      }
      return true;
		} catch (Exception $e) {
        return false;
		}
	}

  /*
   * 客服消息接口 
   */
  public static function serviceApi($uid, $type, $options=array())
  {
    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
    // 给用户发多条记录
    $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
    if ($type == 'text') {
      $body = array(
        "touser" => $uid,
        "msgtype" => "text",
        "text" => array(
          "content" => $options['content'],
        ),
      );
    } elseif($type == 'image') {
      $body = array(
        "touser" => $uid,
        "msgtype" => "image",
        "image" => array(
          "media_id" => $options['media_id'],
        ),
      );
    }

    if(version_compare(PHP_VERSION,'5.4.0','<')){
      $body = json_encode($body);
      $body = preg_replace_callback("#\\\u([0-9a-f]{4})#i",function($matchs){
           return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
      },$body);
    }else{
      $body = json_encode($body, JSON_UNESCAPED_UNICODE); 
    }

    try {
      Sher_Core_Helper_Util::request($url, $body, 'POST');
    } catch(Exception $e) {
      Doggy_Log_Helper::debug("调用客服接口失败！: ".$e->getMessage());
    }
  
  }

  /*
   * 生成二维码接口
   * param $type 1.临时；2.永久；
   * qrData 二维码数据流
   */
  public static function genQr($type, $options=array())
  {
    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
    $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $access_token;
    $body = array(
      'expire_seconds' => 2592000,
      'action_name' => "QR_STR_SCENE",
      'action_info' => array(
        'scene' => array(
          'scene_str' => $options['scene_str']
        ),
      ),
    );
    try{
      if(version_compare(PHP_VERSION,'5.4.0','<')){
        $body = json_encode($body);
        $body = preg_replace_callback("#\\\u([0-9a-f]{4})#i",function($matchs){
             return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
        },$body);
      }else{
        $body = json_encode($body, JSON_UNESCAPED_UNICODE); 
      }
      $result = Sher_Core_Helper_Util::request($url, $body, 'POST');
      $result = json_decode($result, true);   
    }catch(Exception $e) {
      $result['errcode'] = '500';
      $result['errmsg'] = '系统错误';
    }

    return $result;
  }

  /*
   * 上传素材库接口
   * param $type 1.image；2.voice 3.video; 4.thumb；
   * param $data 数据流
   * param $evt 1.临时；2.永久；
   * qrData 二维码数据流
   */
  public static function uploadMedia($type, $path, $evt=1, $options=array())
  {
    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
    $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $access_token. "&type=" . $type;

    if (class_exists('CURLFile')) {
      $real_path =  new CURLFile(realpath($path));
    } else {
      $real_path = '@' . realpath($path);
    }

    $body = array(
      'media' => $real_path
    );
    $ch = curl_init();
    $params = array();
    $params[CURLOPT_URL] = $url;    //请求url地址
    $params[CURLOPT_HEADER] = false; //是否返回响应头信息
    $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
    $params[CURLOPT_FOLLOWLOCATION] = false; //是否重定向
    $params[CURLOPT_POST] = true;
    $params[CURLOPT_POSTFIELDS] = $body;

    curl_setopt_array($ch, $params); //传入curl参数
    $result = curl_exec($ch); //执行
    // 删除文件
    if ($options['uid']) {
      unlink(sprintf("/tmp/wx_d3in/*%s.jpg", $options['uid']));
    }
    curl_close($ch); //关闭连接
    $result = json_decode($result, true);
    return $result;
  }

  /*
   * 查询公号统计
   */
  public static function fetchOrCreatePublic($uid, &$model=null)
  {
    if (!$model) {
      $model = new Sher_Core_Model_PublicNumber();
    }
    $obj = $model->first(array('uid'=> $uid));
    if (!$obj) {
      $row = array(
        'uid' => $uid,
        'mark' => Sher_Core_Helper_Util::generate_mongo_id(),
        'is_follow' => 1,
        'follow_count' => 1,
        'type' => 1,
      );
      $model->create($row);
      $obj = $model->first(array('uid' => $uid));
    }
    return $obj;
  }

  /**
   * 获取用户基本信息
   */
  public static function fetchUserInfo($uid) {
    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
    $url = "https://api.weixin.qq.com/cgi-bin/user/info";
    $body = array(
      'access_token' => $access_token,
      'openid' => $uid,
      'lang' => "zh_CN",
    );
    $result = Sher_Core_Helper_Util::request($url, $body, 'GET');
    $result = json_decode($result, true);
    return $result;
  }

  /**
   * 生成海报
   */
  public static function genPoster($avaUrl, $qrUrl, $options=array())
  {
    $result = array('code'=> 0, 'message'=> '');
    try {
      $uid = $options['uid'];
      //$cmd = '';
      //$gen_path = '/tmp/wx_d3in';
      $posUrl = '/opt/project/static/phenix/wx_d3in/bg.jpeg';
      //$posUrl = 'http://p4.taihuoniao.com/asset/181101/5bda973320de8d9c4e8b8300-1-hu.jpg';
      //$final_path = sprintf("%s/final_%s.jpg", $gen_path, $uid);

      // 图片处理采用原生命名，加快速度
      if ($qrUrl) {
        $qrGmagick = new Gmagick($qrUrl);
        // 裁剪缩放
        $qrGmagick->scaleimage(175, 175);
        //$qr_gen_path = sprintf("%s/qr_%s.jpg", $gen_path, $uid);
        //$cmd = sprintf("gm convert %s -thumbnail '175x175^' -gravity center -extent 175x175 %s && gm composite -geometry +110+930 %s %s %s", $qrUrl, $qr_gen_path, $posUrl, $qr_gen_path, $final_path);
        //exec($cmd);
        
      }

      if ($avaUrl) {
        //$img = file_get_contents($avaUrl);
        //file_put_contents($ava_gen_path,$img);
        $avaGmagick = new Gmagick($avaUrl);
        // 裁剪缩放
        $avaGmagick->scaleimage(170, 170);
        //$ava_bg_url = 'http://p4.taihuoniao.com/asset/181102/5bdbb15020de8da74e8b9130-2-hu.jpg';
        $ava_bg_url = '/opt/project/static/phenix/wx_d3in/ava_bg.png';
        $bgGmagick = new Gmagick($ava_bg_url);
        //$ava_gen_path = sprintf("%s/ava_%s.jpg", $gen_path, $uid);

        //$cmd = sprintf("gm convert %s -thumbnail '170x170^' -gravity center -extent 170x170 %s && gm composite -geometry +0+0 %s %s %s && gm composite -geometry +290+10 %s %s %s", $ava_gen_path, $ava_gen_path, $ava_bg_url, $ava_gen_path, $ava_gen_path, $ava_gen_path, $final_path, $final_path);
        //exec($cmd);
      }

      $posGmagick = new Gmagick($posUrl);
      if ($qrUrl) {
        $posGmagick->compositeimage($qrGmagick, 1, 110, 930);
      }
      if ($avaUrl) {
        $posGmagick->compositeimage($avaGmagick, 1, 290, 10);
        $posGmagick->compositeimage($bgGmagick, 1, 290, 10);
      }
      if ($qrUrl) {
        $qrGmagick->destroy();
      }
      if ($avaUrl) {
        $avaGmagick->destroy();
        $bgGmagick->destroy();
      }
      $path = sprintf("/tmp/wx_d3in_%s.jpg", rand(1000,9999));
      $posGmagick->write($path);
      $posGmagick->destroy();

      // 上传至素材库
      $mediaResult = self::uploadMedia('image', $path, 1, array('uid'=>$uid));
      if (!$mediaResult || isset($mediaResult['errcode'])) {
        $result['code'] = 500;
        $result['message'] = $mediaResult['errmsg'];
      }
      $result['data'] = $mediaResult;
    }catch(Exception $e) {
      Doggy_Log_Helper::debug("error: ".$e->getMessage());
      $result['code'] = 500;
      $result['message'] = $e->getMessage();
    }
    return $result;
  }


}
