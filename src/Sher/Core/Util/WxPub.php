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
    $body = json_encode($body, JSON_UNESCAPED_UNICODE);   

    try {
      Sher_Core_Helper_Util::request($url, $body, 'POST');
    } catch(Exception $e) {
      Doggy_Log_Helper::debug("调用客服接口失败！: ".$e->getMessage());
    }
  
  }

  /*
   * 生成二维码接口
   * param $type 1.临时；2.永久；
   */
  public static function genQr($type, $options=array())
  {
    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
    $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $access_token;
    $body = '{"expire_seconds": 2592000, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}';
    $body = array(
      'expire_seconds' => 2592000,
      'action_name' => "QR_STR_SCENE",
      'action_info' => array(
        'scene' => array(
          'scene_str' => $options['scene_str']
        ),
      ),
    );
    $body = json_encode($body, JSON_UNESCAPED_UNICODE); 
    $result = Sher_Core_Helper_Util::request($url, $body, 'POST');
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


}
