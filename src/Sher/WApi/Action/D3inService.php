<?php
/**
 * 铟立方未来商店服务号接口
 * @author tianshuai 
 */
class Sher_WApi_Action_D3inService extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {

	protected $filter_auth_methods = array('execute', 'gen_menu', 'del_menu');
		
	/**
	 * 初始化参数
	 */
	public function _init() {

	}
		
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->entry();
	}

  /**
   * Fiu 
   */
  public function entry(){
    include_once "wx-pub-encrypt/wxBizMsgCrypt.php";
    Doggy_Log_Helper::debug("获取参数信息: ".json_encode($this->stash));
		//获取通知的数据
		//$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

    $signature = $this->stash['signature'];
    $echostr = $this->stash['echostr'];
    $timestamp = $this->stash['timestamp'];
    $nonce = $this->stash['nonce'];
    $openid = $this->stash['openid'];
    $msgSignature = $this->stash['msg_signature'];

    $token = Doggy_Config::$vars['app.d3in_wechat']['token'];
    $encodingAesKey = Doggy_Config::$vars['app.d3in_wechat']['encoding_aes_key'];
    $appId = Doggy_Config::$vars['app.d3in_wechat']['app_id'];

    $isCheck = Sher_Core_Util_WxPub::checkSignature($token, $timestamp, $nonce, $signature);
    if (!$isCheck) {
      Doggy_Log_Helper::debug("验证来源失败！");     
      echo '';
      return false;
    }

    $pc = new WXBizMsgCrypt($token, $encodingAesKey, $appId);

    // 第三方收到公众号平台发送的消息
    $msg = '';
    $errCode = $pc->decryptMsg($msgSignature, $timestamp, $nonce, $xml, $msg);
    if ($errCode != 0) {
      Doggy_Log_Helper::debug("解密报错: " . $errCode);
      echo '';
      return;
    }
    Doggy_Log_Helper::debug("解密后: " . $msg);
    $xml_tree = new DOMDocument();
    $xml_tree->loadXML($msg);
    $uid_arr = $xml_tree->getElementsByTagName('FromUserName');
    $mtype_arr = $xml_tree->getElementsByTagName('MsgType');
    $event_arr = $xml_tree->getElementsByTagName('Event');
    $event_key_arr = $xml_tree->getElementsByTagName('EventKey');
    $uid = $uid->item(0)->nodeValue;
    $mtype = $mtype->item(0)->nodeValue;
    $event = $event->item(0)->nodeValue;
    $event_key = $event_key_arr->item(0)->nodeValue;

    Doggy_Log_Helper::debug(sprintf("解析数据: uid-%s|type-%s|", $uid, $mtype));

  }

  /**
   * 创建菜单
   */
  public function gen_menu(){
    Doggy_Log_Helper::debug("生成铟立方公众号菜单... ");

    $token = Sher_Core_Util_WechatJs::wx_get_token(2);
    Doggy_Log_Helper::debug("获取access token: $token ");
    $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=". $token;

    $menu = array(
      "button" => array(
        array(
          "type" => "click",
          "name" =>"今日歌曲",
          "key" => "V1001_TODAY_MUSIC"     
        ),
        array(
          "name" => "菜单",
          "sub_button" => array(
            array(
              "type" => "view",
              "name" => "搜索",
              "url" => "http://www.soso.com/"
            ),
            array(
              "type" => "click",
              "name" => "赞一下我们",
              "key" => "V1001_GOOD"
            ),
          ),
        ),
      ),
    );

    $menu = json_encode($menu, JSON_UNESCAPED_UNICODE);

    try {
      $result = Sher_Core_Helper_Util::request($url, $menu, 'POST');
      $result = json_decode($result, true);
      if ($result['errcode']) {
        echo "创建失败 code: $result[errode], message: $result[errmsg]";
        return;
      }
      Doggy_Log_Helper::debug("gen pub menu ok!");
      echo "create ok!";
    } catch(Exception $e) {
      echo "创建失败: " . $e->getMessage();
    }
    return "ok";
  }

  /**
   * 删除菜单
   */
  public function del_menu(){
    Doggy_Log_Helper::debug("删除铟立方公众号菜单... ");

    $token = Sher_Core_Util_WechatJs::wx_get_token(2);
    Doggy_Log_Helper::debug("获取access token: $token ");
    $url = "https://api.weixin.qq.com/cgi-bin/menu/delete";

    try {
      $result = Sher_Core_Helper_Util::request($url, array('access_token' => $token), 'GET');
      $result = json_decode($result, true);
      if ($result['errcode']) {
        echo "删除失败 code: $result[errode], message: $result[errmsg]";
        return;
      }
      Doggy_Log_Helper::debug("del pub menu ok!");
      echo "del ok!";
    } catch(Exception $e) {
      echo "删除失败: " . $e->getMessage();
    }
    return "ok";
  }

}

