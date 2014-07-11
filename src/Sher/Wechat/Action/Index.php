<?php
/**
 * 微信（服务号）店铺首页
 * @author purpen
 */
class Sher_Wechat_Action_Index extends Sher_Core_Action_Authorize implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'signature' => '',
		'timestamp' => '',
		'nonce' => '',
		'echostr' => '',
		'page' => 1 ,
		'ref' => '',
	);
	
	// 配置微信参数
	public $options = array();
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/wechat/index.html';
	
	protected $exclude_method_list = array('execute', 'verify', 'menu');
	
	/**
	 * 初始化
	 */
	public function _init() {
		$this->options = array(
			'token'=>Doggy_Config::$vars['app.wechat.ser_token'],
			'appid'=>Doggy_Config::$vars['app.wechat.ser_app_id'],
			'appsecret'=>Doggy_Config::$vars['app.wechat.ser_app_secret'],
			'partnerid'=>Doggy_Config::$vars['app.wechat.ser_partner_id'],
			'partnerkey'=>Doggy_Config::$vars['app.wechat.ser_partner_key'],
			'paysignkey'=>'' //商户签名密钥Key
		);
    }
	
	/**
	 * 微信入口
	 */
	public function execute(){
		Doggy_Log_Helper::warn("Get wexin request!");
		
		$weObj = new Sher_Core_Util_Wechat($this->options);
		// $weObj->valid();
		$type = $weObj->getRev()->getRevType();
		$event = $weObj->getRev()->getRevEvent();
		
		Doggy_Log_Helper::warn("Get wexin type[$type], event[".$event['key']."]!");
		Doggy_Log_Helper::warn("Get  rev content [".json_encode($revcontent)."]!");
		
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
					$rev_data = $weObj->getRev()->getRevData();
					$data = $this->handle_event($event, $rev_data);
					if ($event['key'] == 'MENU_KEY_SOCIAL_CONTACT'){ // 联系我们
						$result = $weObj->text($data)->reply(array(), true);
					}else{
						if ($event['event'] == 'subscribe' || strtolower($event['event']) == 'scan'){ // 扫描二维码关注
							$result = $weObj->text($data)->reply(array(), true);
						} else {
							$result = $weObj->news($data)->reply(array(), true);
						}
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
	protected function handle_text($content, $rev_data=array()){
		Doggy_Log_Helper::warn("Handle wexin content[$content]!");
		// 转换为小写
		$content = strtolower($content);
		$result = array();
		switch($content){
			case 'dm':
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
	protected function handle_event($evt, $rev_data=array()){
		$event = $evt['event'];
		$key = $evt['key'];
		
		Doggy_Log_Helper::warn("Handle event key[$key]!");
		
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
		
		Doggy_Log_Helper::warn("Handle event [$event]!");
		$event = strtolower($event);
		// 扫描关注二维码
		switch($event){
			case 'subscribe':
				$result = $this->handle_subscribe($rev_data);
				break;
			case 'scan':
				$result = $this->handle_scan($rev_data);
				break;
		}
		
		Doggy_Log_Helper::warn("Handle event result[".json_encode($result)."]!");
		
		return $result;
	}
	
	/**
	 * 扫描带参数二维码事件, 用户未关注时
	 */
	protected function handle_subscribe($rev_data=array()){
		$open_id = $rev_data['FromUserName'];
		$scene_id = str_replace('qrscene_', '', $rev_data['EventKey']);
		
		Doggy_Log_Helper::warn("Handle event subscribe [$scene_id]!");
		
		// 注册并实现登录
		$user = Sher_Core_Helper_Auth::create_weixin_user($open_id, $scene_id);
		if ($user){
			$user_id = $user['_id'];
			$nickname = $user['nickname'];
			
			Sher_Core_Helper_Auth::update_user_session($scene_id, $user['_id']);
			
			$subscribe = "微信通过验证！\n您已授权从网页版登录到太火鸟，当前授权帐号信息：ID: ${user_id} \n昵称：${nickname} 。\n";
		
			return $subscribe;
		}
	}
	
	/**
	 * 扫描带参数二维码事件, 用户关注时
	 */
	protected function handle_scan($rev_data=array()){
		$open_id = $rev_data['FromUserName'];
		$scene_id = $rev_data['EventKey'];
		
		Doggy_Log_Helper::warn("Handle event scan [$scene_id]!");
		
		// 实现登录
		$user = Sher_Core_Helper_Auth::create_weixin_user($open_id, $scene_id);
		if ($user){
			$user_id = $user['_id'];
			$nickname = $user['nickname'];
			
			Sher_Core_Helper_Auth::update_user_session($scene_id, $user_id);
			
			$welcome = "欢迎回来！\n您在太火鸟的帐号信息：ID: ${user_id}\n昵称：${nickname} 。\n";
		
			return $welcome;
		}
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
太火鸟是一个创新产品孵化加速器兼原创产品社会化电商平台，将于8月底正式启动，届时会推出10款原创智能创新产品，期待您的持续关注。了解更多，请猛戳：<a href="http://www.taihuoniao.com">www.taihuoniao.com</a>；参与全球首款革命性智能空气净化器活动，请回复：dm。';
		
		return $welcome;
	}
	
	/**
	 * 最新产品列表
	 */
	protected function newest() {
		$result = array(
			"0" => array(
				'Title' => '全球首款革命性智能空气净化器活动',
				'Description'=>'全球首款革命性智能空气净化器活动,现在预订就有机会赢取大奖',
				'PicUrl'=>'http://frstatic.qiniudn.com/images/product/dm-m-banner.jpg',
				'Url'=>'http://www.taihuoniao.com/dm'
			)
		);
		
		return $result;
	}
	
	/**
	 * 设置微信自定义菜单
	 */
	public function menu(){
		$we = new Sher_Core_Util_Wechat($this->options);
			
		// 设置菜单
		$newmenu =  array(
			"button"=>
				array(
					array(
						'type' => 'view',
						'name' => '精选商品',
						"url" => "http://www.taihuoniao.com/wechat/shop",
					),
					array(
						'type' => 'view',
						'name' => '享优惠',
						"url" => "http://wd.koudai.com/?userid=164729694",
					),
					array(
						'type' => 'click',
						'name' => '[我]',
						'key' => 'MENU_KEY_Social',
						'sub_button' => array(
							array(
						   	 	"type" => "click",
						   	 	"name" => "维权",
								"key" => "MENU_KEY_FEEDBACK"
							),
							array(
						   	 	"type" => "view",
						   	 	"name" => "我的订单",
								"url" => "http://www.taihuoniao.com/my/orders"
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
	
	/**
	 * 查看菜单
	 */
	public function get_menu(){
		$we = new Sher_Core_Util_Wechat($this->options);
		
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