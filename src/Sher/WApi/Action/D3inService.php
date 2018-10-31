<?php
/**
 * 铟立方未来商店服务号接口
 * @author tianshuai 
 */
class Sher_WApi_Action_D3inService extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {

	protected $filter_auth_methods = array('execute', 'gen_menu', 'del_menu', 'fetch_mertail');
		
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
   * Entry
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

    $access_token = Sher_Core_Util_WechatJs::wx_get_token(2);

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
        $rEvent = 'text';
        $contentStr = '';
        $mediaId = '';
        $picUrl = '';

        if ($content == '测试') {
          $contentStr = '好好测哦~';
        }elseif ($content == '超级红包') {
          $contentStr = "戳（链接）抽奖，一元抢戴森卷发棒\n更有1500元红包限时领。\n转发个人海报，获得好友支持，即可额外获得2次抽奖机会。\n↓";
        } elseif($content == '我要嗨购') {
          $rEvent = 'image';
          $mediaId = 'dRfSIOBu6JBL8PBXEY5wB9shXpcJK9GoVeSmc_Qj-ag';
        }

        if (!$contentStr && !$mediaId && !$picUrl) {
          echo "success";
          return;
        }

        if ($rEvent == 'text') {
          // 给用户发客服回复
          Sher_Core_Util_WxPub::serviceApi($uid, 'text', array('content'=>$contentStr));

          // 生成海报
          if ($content == '超级红包') {
            // 获取用户标识
            $public_number_model = new Sher_Core_Model_PublicNumber();
            try {
              $obj = Sher_Core_Util_WxPub::fetchOrCreatePublic($uid, $public_number_model);
              $result = Sher_Core_Util_WxPub::genQr(1, array('acene_str'=>$obj['mark']));
              if(!$result) {
                Doggy_Log_Helper::debug("获取二维码失败！");
              }
              Doggy_Log_Helper::debug(json_encode($result));

              $userResult = Sher_Core_Util_WxPub::fetchUserInfo($uid);
              if ($userResult) {
                $row = array(
                  'nickname' => $userResult['nickname'],
                  'avatar' => $userResult['headimgurl'],
                  'sex' => $userResult['sex'],
                  'country' => $userResult['country'],
                  'province' => $userResult['province'],
                  'city' => $userResult['city'],
                  'unionid' => $userResult['unionid'],
                );

                // 更新到公号用户
                $userOk = $public_number_model->update_set((string)$obj['_id'], array('user_info'=>$row));
                if ($userOk) {
                  Doggy_Log_Helper::debug('更新用户信息成功！');               
                }else{
                  Doggy_Log_Helper::debug('更新用户信息失败！');               
                }
              }
              Doggy_Log_Helper::debug(json_encode($userResult));
            } catch(Exception $e) {
              Doggy_Log_Helper::debug("获取二维码失败！". $e->getMessage());
            }
          }

        }elseif($rEvent == 'image') {
          if ($mediaId) {
            // 给用户发客服回复
            Sher_Core_Util_WxPub::serviceApi($uid, 'image', array('media_id'=>$mediaId));
          }
        }

        Doggy_Log_Helper::debug(sprintf("content: %s-%s", $content, $msg_id));
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
          $obj = Sher_Core_Util_WxPub::fetchOrCreatePublic($uid, $public_number_model);
          if ($obj) {
            $public_number_model->update_set((string)$obj['_id'], array('follow_count'=> $obj['follow_count']-1, 'is_follow'=>0));
          }
        }else if ($event == 'subscribe') {
          // 记录该用户数据
          $obj = Sher_Core_Util_WxPub::fetchOrCreatePublic($uid, $public_number_model);
          if ($obj) {
            $public_number_model->update_set((string)$obj['_id'], array('follow_count'=> $obj['follow_count']+1, 'is_follow'=>1));
          }
          $mark = '';
          if ($obj) {
            $mark = $obj['mark'];
          }

          // 给用户发客服回复
          Sher_Core_Util_WxPub::serviceApi($uid, 'text', array('content'=>"嗨，欢迎来到铟立方未来商店\n转发个人海报，获得好友支持，额外获得2次抽奖机会。\n↓"));
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

  /**
   * 获取素材列表
   */
  public function fetch_mertail(){
    Doggy_Log_Helper::debug("获取素材列表... ");

    $token = Sher_Core_Util_WechatJs::wx_get_token(2);
    Doggy_Log_Helper::debug("获取access token: $token ");
    $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=".$token;
    $param = array(
      "type" => 'image',
      "offset" => 1,
      "count" => 1000,
    );
    $param = json_encode($param, JSON_UNESCAPED_UNICODE);

    try {
      $result = Sher_Core_Helper_Util::request($url, $param, 'POST');
      $result = json_decode($result, true);
      print_r($result);
      if ($result['errcode']) {
        echo "获取失败 code: $result[errode], message: $result[errmsg]";
        return;
      }
      Doggy_Log_Helper::debug("fetch mertail ok!");
      echo "fetch material ok!";
    } catch(Exception $e) {
      echo "获取成功: " . $e->getMessage();
    }
    return "ok";
  }

}

