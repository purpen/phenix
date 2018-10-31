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


}
