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
	
	protected $exclude_method_list = array('execute', 'verify', 'menu');

	/**
	 * 微信入口
	 */
	public function execute(){
		Doggy_Log_Helper::warn("Get wexin request!");
		
		$options = array(
			'token'=>Doggy_Config::$vars['app.wechat.token'], //填写你设定的key
			'appid'=>Doggy_Config::$vars['app.wechat.app_id'], //填写高级调用功能的app id
			'appsecret'=>Doggy_Config::$vars['app.wechat.app_secret'], //填写高级调用功能的密钥
			'partnerid'=>'', //财付通商户身份标识
			'partnerkey'=>'', //财付通商户权限密钥Key
			'paysignkey'=>'' //商户签名密钥Key
		);
		
		$weObj = new Sher_Core_Util_Wechat($options);
		// $weObj->valid();
		$type = $weObj->getRev()->getRevType();
		$event = $weObj->getRev()->getRevEvent();
		
		Doggy_Log_Helper::warn("Get wexin tyep[$type], event[".$event['key']."]!");
		
		switch($type) {
			case Sher_Core_Util_Wechat::MSGTYPE_TEXT:
				$result = $weObj->text("hello, I'm wechat")->reply(array(), true);
				break;
			case Sher_Core_Util_Wechat::MSGTYPE_EVENT:
				$data = $this->handle_event($event); 
				$result = $weObj->news($data)->reply(array(), true);
				break;
			case Sher_Core_Util_Wechat::MSGTYPE_IMAGE:
				break;
			default:
				$result = $weObj->text("help info")->reply(array(), true);
				break;
		}
		return $this->to_raw($result);
	}
	
	/**
	 * 消息事件
	 */
	protected function handle_event($event){
		$key = $event['key'];
		Doggy_Log_Helper::warn("Handle event[$key]!");
		$result = array();
		switch($key){
			case 'MENU_KEY_SHOP_NEWEST':
				Doggy_Log_Helper::warn("Handle event to start MENU_KEY_SHOP_NEWEST!");
				$result = array(
					"0" => array(
						'Title' => 'Goccia全球最小的运动可穿戴',
						'Description'=>'Goccia全球最小的运动可穿戴国内首发,现在预订就有机会赢取大奖',
						'PicUrl'=>'http://frstatic.qiniudn.com/images/g-banner.jpg',
						'Url'=>'http://www.taihuoniao.com/goccia'
					)
				);
				break;
			case 'MENU_KEY_SHOP_STAR':
				break;
			default:
				break;
		}
		Doggy_Log_Helper::warn("Handle event result[$result]!");
		
		return $result;
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