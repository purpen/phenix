<?php
/**
 * 微信店铺首页
 * @author purpen
 */
class Sher_Wechat_Action_Index extends Sher_Core_Action_Authorize {
	public $stash = array(
		'signature'=>'',
		'timestamp'=>'',
		'nonce'=>'',
		'echostr'=>'',
		'page'=>1,
		'ref'=>'',
	);
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/wechat/index.html';
	
	protected $exclude_method_list = array('execute', 'verify');

	/**
	 * 微信入口
	 */
	public function execute(){
		$options = array(
			'token'=>Doggy_Config::$vars['app.wechat.token'], //填写你设定的key
			'appid'=>Doggy_Config::$vars['app.wechat.app_id'], //填写高级调用功能的app id
			'appsecret'=>Doggy_Config::$vars['app.wechat.app_secret'], //填写高级调用功能的密钥
			'partnerid'=>'', //财付通商户身份标识
			'partnerkey'=>'', //财付通商户权限密钥Key
			'paysignkey'=>'' //商户签名密钥Key
		);
		
		$weObj = new Sher_Core_Util_Wechat($options);
		$weObj->valid();
		$type = $weObj->getRev()->getRevType();
		
		switch($type) {
			case Wechat::MSGTYPE_TEXT:
				$weObj->text("hello, I'm wechat")->reply();
					exit;
					break;
				case Wechat::MSGTYPE_EVENT:
					break;
				case Wechat::MSGTYPE_IMAGE:
					break;
				default:
					$weObj->text("help info")->reply();
		}
	}
	
	
    /**
     * 验证Token
	 *
     * @return string
     */
    public function verify() {
        $echoStr = $this->stash["echostr"];
		
        //valid signature , option
        if($this->check_signature()){
			return $this->to_raw($echoStr);
        }
		
		return $this->to_raw('Erorr: not match!');
    }
	/**
	 * 微信自定义菜单
	 */
	public function menu(){
		$options = array(
			'token'=>Doggy_Config::$vars['app.wechat.token'], //填写你设定的key
			'appid'=>Doggy_Config::$vars['app.wechat.app_id'], //填写高级调用功能的app id
			'appsecret'=>Doggy_Config::$vars['app.wechat.app_secret'], //填写高级调用功能的密钥
			'partnerid'=>'', //财付通商户身份标识
			'partnerkey'=>'', //财付通商户权限密钥Key
			'paysignkey'=>'' //商户签名密钥Key
		);
		
		$we = new Sher_Core_Util_Wechat($options);
		// 设置菜单
		$newmenu =  array(
			"button"=>
				array(
					array(
						'type'=>'click',
						'name'=>'创意市集',
						'key'=>'MENU_KEY_SHOP',
						'sub_button' => array(
							array(
						   	 	"type" => "click",
						   	 	"name" => "新品推荐",
						   	 	"key" => "MENU_KEY_SHOP_NEWEST"
							),
							array(
						   	 	"type" => "click",
						   	 	"name" => "明星产品",
						   	 	"key" => "MENU_KEY_SHOP_STAR"
							)
						)
					),
					array(
						'type'=>'click',
						'name'=>'互动社区',
						'key'=>'MENU_KEY_Social',
						'sub_button' => array(
							array(
						   	 	"type" => "click",
						   	 	"name" => "微社区",
						   	 	"key" => "MENU_KEY_SOCIAL_WEIS"
							),
							array(
						   	 	"type" => "click",
						   	 	"name" => "联系我们",
						   	 	"key" => "MENU_KEY_SOCIAL_CONTACT"
							)
						)
					)
				)
		);
		$result = $we->createMenu($newmenu);
		
		if($result){
			return $this->to_raw('设置菜单成功！');
		}else{
			return $this->to_raw('设置菜单失败！');
		}
	}
	
	public function get_menu(){
		$options = array(
			'token'=>Doggy_Config::$vars['app.wechat.token'], //填写你设定的key
			'appid'=>Doggy_Config::$vars['app.wechat.app_id'], //填写高级调用功能的app id
			'appsecret'=>Doggy_Config::$vars['app.wechat.app_secret'], //填写高级调用功能的密钥
			'partnerid'=>'', //财付通商户身份标识
			'partnerkey'=>'', //财付通商户权限密钥Key
			'paysignkey'=>'' //商户签名密钥Key
		);
		
		$we = new Sher_Core_Util_Wechat($options);
		
		$menu = $we->getMenu();
		
		print_r($menu);
	}
	
	
}
?>