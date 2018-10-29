<?php
/**
 * 铟立方未来商店服务号接口
 * @author tianshuai 
 */
class Sher_WApi_Action_D3inService extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {

	protected $filter_auth_methods = array('execute', 'gen_menu');
		
	/**
	 * 初始化参数
	 */
	public function _init() {

	}
		
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->payment();
	}

  /**
   * Fiu 
   */
  public function payment(){
    Doggy_Log_Helper::warn("获取参数信息: ".json_encode($this->stash));
    $signature = $this->stash['signature'];
    $echostr = $this->stash['echostr'];
    $timestamp = $this->stash['timestamp'];
    $nonce = $this->stash['nonce'];
    $openid = $this->stash['openid'];

    //$check_result = Sher_Core_Util_WxPub::getSHA1();


    echo "test";
    return "ok";
  }

  /**
   * 创建菜单
   */
  public function gen_menu(){
    Doggy_Log_Helper::warn("生成铟立方公众号菜单... ");

    $token = Sher_Core_Util_WechatJs::wx_get_token(2);
    Doggy_Log_Helper::debug("获取access token: $token ");
    $url = "https://api.weixin.qq.com/cgi-bin/menu/create";

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
            "type" => "view",
            "name" => "搜索",
            "url" => "http://www.soso.com/"
          ),
          array(
            "type" => "miniprogram",
            "name" => "wxa",
            "url" => "http://mp.weixin.qq.com",
            "appid" => "wx286b93c14bbf93aa",
            "pagepath" => "pages/lunar/index"
          ),
          array(
            "type" => "click",
            "name" => "赞一下我们",
            "key" => "V1001_GOOD"
          ),
        ),
      ),
    );

    $param = array(
      'access_token' => $token,
      'body' => $menu,
    );

    try {
      $result = Sher_Core_Helper_Util::request($url, $param, 'POST');
      $result = json_decode($result, true);
      echo json_encode($result);
      if ($result['errcode']) {
        echo "创建失败 code: $result[errode], message: $result[errmsg]";
      }
      Doggy_Log_Helper::debug("gen public menu ok!");
      echo "create ok!";
    } catch(Exception $e) {
      echo "创建失败: " . $e->getMessage();
    }
    return "ok";
  }

}

