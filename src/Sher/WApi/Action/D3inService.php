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
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

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
    $to_user_name_arr = $xml_tree->getElementsByTagName('ToUserName');
    $uid_arr = $xml_tree->getElementsByTagName('FromUserName');
    $c_time_arr = $xml_tree->getElementsByTagName('CreateTime');
    $mtype_arr = $xml_tree->getElementsByTagName('MsgType');
    $uid = $uid_arr->item(0)->nodeValue;
    $to_user_name = $to_user_name_arr->item(0)->nodeValue;
    $c_time = $c_time_arr->item(0)->nodeValue;
    $mtype = $mtype_arr->item(0)->nodeValue;

    Doggy_Log_Helper::debug(sprintf("解析数据-类型: %s", $mtype));
    switch ($mtype) { // 文字
      case 'text':
        $content_arr = $xml_tree->getElementsByTagName('Content');
        $msg_id_arr = $xml_tree->getElementsByTagName('MsgId');
        $msg_id = $msg_id_arr->item(0)->nodeValue;
        $content = $content_arr->item(0)->nodeValue;

        if ($content == '测试') {
          $contentStr = '好好测哦~';
        } elseif($content == 'test') {
          $contentStr = 'good boy~';
        }elseif ($content == '超级红包') {
          $contentStr = "戳（链接）抽奖，一元抢戴森卷发棒\n更有1500元红包限时领。\n转发个人海报，获得好友支持，即可额外获得2次抽奖机会。";
        }else{
          $contentStr = '';
        }

        if (!$contentStr) {
          echo "success";
          return;
        }

        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <MsgId>![CDATA[%s]]</MsgId>
            </xml>";

        Doggy_Log_Helper::debug(sprintf("content: %s-%s", $content, $msg_id));

        $resultStr = sprintf($textTpl, $uid, $to_user_name, $c_time, $mtype, $contentStr, $msg_id);
        echo $resultStr;
        break;
      case 'event': // 事件
        $event_arr = $xml_tree->getElementsByTagName('Event');
        $event_key_arr = $xml_tree->getElementsByTagName('EventKey');
        $event = $event_arr->item(0)->nodeValue;
        $event_key = $event_key_arr->item(0)->nodeValue;

        Doggy_Log_Helper::debug(sprintf("Event: %s-%s", $event, $event_key));
        $public_number_model = new Sher_Core_Model_PublicNumber();
        if ($event == 'unsubscribe') {
          // 记录该用户数据
          $obj = $public_number_model->first(array('uid'=> $uid));
          if ($obj) {
            $public_number_model->update_set((string)$obj['_id'], array('follow_count'=> $obj['follow_count']-1, 'is_follow'=>0));
          }
        
        }else if ($event == 'subscribe') {
          // 记录该用户数据
          $obj = $public_number_model->first(array('uid'=> $uid));
          if ($obj) {
            $public_number_model->update_set((string)$obj['_id'], array('follow_count'=> $obj['follow_count']+1, 'is_follow'=>1));
          }else{
            $row = array(
              'uid' => $uid,
              'mark' => Sher_Core_Helper_Util::generate_mongo_id(),
              'is_follow' => 1,
              'follow_count' => 1,
              'type' => 1,
            );
            $public_number_model->create($row);
            $obj = $public_number_model->first(array('uid' => $uid));
          }
          $mark = '';
          if ($obj) {
            $mark = $obj['mark'];
          }

          /**
          $textTpl = "<xml>
              <ToUserName><![CDATA[%s]]></ToUserName>
              <FromUserName><![CDATA[%s]]></FromUserName>
              <CreateTime>%s</CreateTime>
              <MsgType><![CDATA[%s]]></MsgType>
              <Content><![CDATA[%s]]></Content>
              </xml>";
          $contentStr = '嗨，欢迎来到铟立方未来商店';
          //$contentStr = "嗨，欢迎来到铟立方未来商店\n先锋设计产品，前沿科技资讯\n新鲜生活方式，智能未来体验\n你的每一次关注，都在为自己喜欢的生活买单。\n铟立方72小时嗨购活动正在进行中，戳 http://t.taihuoniao.com/app/wap/promo/d3in_draw?mark=$mark  参与抽奖即有机会1元抢戴森卷发棒，更有1500元红包限时领。";

          $resultStr = sprintf($textTpl, $uid, $to_user_name, $c_time, 'text', $contentStr);
          echo $resultStr;
          **/

          // 给用户发多条记录
          $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);
          $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
          $body = array(
            "touser" => $uid,
            "msgtype" => "text",
            "text" => array(
              "content" => "嗨，欢迎来到铟立方未来商店",
            ),
          );
          $body1 = array(
            "touser" => $uid,
            "msgtype" => "text",
            "text" => array(
              "content" => "转发个人海报，获得好友支持，额外获得2次抽奖机会。",
            ),
          );
          $body = json_encode($body, JSON_UNESCAPED_UNICODE);
          //$body1 = json_encode($body1, JSON_UNESCAPED_UNICODE);
          try {
            Sher_Core_Helper_Util::request($url, $body, 'POST');
            Sher_Core_Helper_Util::request($url, $body1, 'POST');
          } catch(Exception $e) {
            Doggy_Log_Helper::debug("调用客服接口失败！: ".$e->getMessage());
          }
        }
        break;
      case 'image':
        $media_id_arr = $xml_tree->getElementsByTagName('MediaId');
        $media_id = $media_id_arr->item(0)->nodeValue;

        Doggy_Log_Helper::debug(sprintf("Image: %s", $media_id));

        echo "success";
        break;
      case 'voice':
        $media_id_arr = $xml_tree->getElementsByTagName('MediaId');
        $media_id = $media_id_arr->item(0)->nodeValue;

        Doggy_Log_Helper::debug(sprintf("Voice: %s", $media_id));

        echo "success";
        break;
      default:
        Doggy_Log_Helper::debug("未知获取类型!");
        echo "success";
    }

    return;

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

