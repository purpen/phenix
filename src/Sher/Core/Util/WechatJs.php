<?php
/**
 *	微信公众平台PHP-JSSDK, 官方API部分
 *  @author  purpen <purpen.w@gmail.com>
 */
class Sher_Core_Util_WechatJs extends Doggy_Object {

	  /**
	   * 获取token
	   * 注意：这里需要将获取到的token缓存起来（或写到数据库中）
	   * 不能频繁的访问https://api.weixin.qq.com/cgi-bin/token，每日有次数限制
	   * 通过此接口返回的token的有效期目前为2小时。令牌失效后，JS-SDK也就不能用了。
	   * 因此，这里将token值缓存1小时，比2小时小。缓存失效后，再从接口获取新的token，这样就可以避免token失效。
	   */
	  public static function wx_get_token() {
		  $redis = new Sher_Core_Cache_Redis();
		  $token_key = 'wx_token';
		  $token = $redis->get($token_key);
		  if (!$token) {
				Doggy_Log_Helper::warn('wechat token is generate!');
				$app_id = Doggy_Config::$vars['app.wechat.app_id'];
				$app_secret = Doggy_Config::$vars['app.wechat.app_secret'];
				$res = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$app_id.'&secret='.$app_secret);
				$res = json_decode($res, true);
        if(isset($res['errcode']) && !empty($res['errcode'])){
          return $res['errmsg'];
        }else{
          $token = $res['access_token'];
          $redis->set($token_key, $token, 3600);       
        }
		  }else{
				Doggy_Log_Helper::warn('wechat token is read redis!');  
		  }
		  return $token;
	  }
	
	  /**
	   * fetch jsapi_ticket
	   * 注意：这里需要将获取到的ticket缓存起来（或写到数据库中）
	   * ticket和token一样，不能频繁的访问接口来获取，在每次获取后，我们把它保存起来。
	   */	
	  public static function wx_get_jsapi_ticket(){
			$redis = new Sher_Core_Cache_Redis();
			$token_key = 'wx_token';
			$ticket_key = 'wx_ticket';
			$ticket = "";
			do{
				  $ticket = $redis->get($ticket_key);
				  if (!empty($ticket)) {
						Doggy_Log_Helper::warn('wechat ticket is read redis!');
						break;
				  }else{
						Doggy_Log_Helper::warn('wechat ticket is generate!');    
				  }
				  $token = $redis->get($token_key);
				  if (empty($token)){
						$token = self::wx_get_token();
				  }
				  if (empty($token)) {
						Doggy_Log_Helper::error("get wechat access token error.");
						break;
				  }
				  $url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi", $token);
				  $res = file_get_contents($url2);
				  $res = json_decode($res, true);
				  $ticket = $res['ticket'];
				  $redis->set($ticket_key, $ticket, 3600);
			}while(0);
			return $ticket;
	  }
}
?>
