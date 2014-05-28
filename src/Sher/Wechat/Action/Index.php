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
		
		Doggy_Log_Helper::warn("Get wexin type[$type], event[".$event['key']."]!");
		
		switch($type) {
			case Sher_Core_Util_Wechat::MSGTYPE_TEXT:
				$revcontent = $weObj->getRev()->getRevContent();
				Doggy_Log_Helper::warn("Get wexin type[$type], content[$revcontent]!");
				if (!empty($revcontent)){
					$data = $this->handle_text($revcontent);
					$result = $weObj->news($data)->reply(array(), true);
				}else{ // 默认欢迎语
					$welcome = $this->welcome();
					$result = $weObj->text($welcome)->reply(array(), true);
				}
				break;
			case Sher_Core_Util_Wechat::MSGTYPE_EVENT:
				if (!isset($event['key']) || empty($event['key'])){  // 默认欢迎语
					$welcome = $this->welcome();
					$result = $weObj->text($welcome)->reply(array(), true);
				}else{
					$data = $this->handle_event($event);
					if ($event['key'] == 'MENU_KEY_SOCIAL_CONTACT'){
						$result = $weObj->text($data)->reply(array(), true);
					}else{
						$result = $weObj->news($data)->reply(array(), true);
					}
				}
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
	 * 处理文本回复
	 */
	protected function handle_text($content){
		Doggy_Log_Helper::warn("Handle wexin content[$content]!");
		// 转换为小写
		$content = strtolower($content);
		$result = array();
		switch($content){
			case 'go':
				$result = $this->newest();
				break;
			default:
				$result = $this->newest();
				break;
		}
		
		Doggy_Log_Helper::warn("Handle text result[".json_encode($result)."]!");
		
		return $result;
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
				$result = $this->newest();
				break;
			case 'MENU_KEY_SHOP_STAR':
				$result = $this->newest();
				break;
			case 'MENU_KEY_SOCIAL_CONTACT':
				$result = $this->contact();
				break;
			default:
				break;
		}
		Doggy_Log_Helper::warn("Handle event result[".json_encode($result)."]!");
		
		return $result;
	}
	
	/**
	 * 联系我们
	 */
	protected function contact(){
		$contact = '如果您有独特的产品实物，如果您有待实现的设计草图，甚至，您只有一个新奇的创意点子，如果您愿意跟我们聊聊，zhangting@taihuoniao.com;如果您对我们的产品有浓厚的兴趣，如果您想批量购买，联系这里liuhui@taihuoniao.com，有优惠！如果您的产品与我们的平台调性很搭，如果您觉得我们一起传播效果更佳，那还等什么，来吧！wangxinyu@taihuoniao.com';
		return $contact;
	}
	
	/**
	 * 订阅自动回复
	 */
	protected function welcome(){
		$welcome = '您好，欢迎关注太火鸟！
太火鸟是一个创新产品孵化加速器兼原创产品社会化电商平台，将于6月底正式启动，届时会推出10款原创智能创新产品，期待您的持续关注。了解更多，请猛戳：<a href="http://www.taihuoniao.com">www.taihuoniao.com</a>；参与Goccia抽奖活动，请回复：go。';
		
		return $welcome;
	}
	
	/**
	 * 最新产品列表
	 */
	protected function newest() {
		$result = array(
			"0" => array(
				'Title' => 'Goccia全球最小的运动可穿戴',
				'Description'=>'Goccia全球最小的运动可穿戴强势来袭,现在预订就有机会赢取大奖',
				'PicUrl'=>'http://frstatic.qiniudn.com/images/goccia-weixin-cover.jpg',
				'Url'=>'http://www.taihuoniao.com/goccia'
			)
		);
		
		return $result;
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
		/*
		array(
	   	 	"type" => "click",
	   	 	"name" => "参与活动",
	   	 	"key" => "MENU_KEY_SHOP_NEWEST"
		),
		array(
	   	 	"type" => "click",
	   	 	"name" => "明星产品",
	   	 	"key" => "MENU_KEY_SHOP_STAR"
		),*/
		$newmenu =  array(
			"button"=>
				array(
					array(
						'type'=>'click',
						'name'=>'创意市集',
						'key'=>'MENU_KEY_SHOP',
						'sub_button' => array(
							array(
						   	 	"type" => "view",
						   	 	"name" => "活动说明",
								"url" => "http://www.taihuoniao.com/wechat/notice"
							),
							array(
						   	 	"type" => "click",
						   	 	"name" => "参与活动",
								"key" => "MENU_KEY_SHOP_NEWEST"
							),
							array(
						   	 	"type" => "view",
						   	 	"name" => "中奖名单",
								"url" => "http://www.taihuoniao.com/activity/winners"
							)
						)
					),
					array(
						'type'=>'click',
						'name'=>'互动社区',
						'key'=>'MENU_KEY_Social',
						'sub_button' => array(
							array(
						   	 	"type" => "view",
						   	 	"name" => "微社区",
								"url" => "http://www.taihuoniao.com/fever"
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
	
	
}
?>