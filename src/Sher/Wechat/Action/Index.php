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
	
	// 微信用户openid
	public $wx_open_id = '';
	
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
		$fromUserName = $weObj->getRev()->getRevFrom();
		
		Doggy_Log_Helper::warn("Get wexin type[$type], event[".$event['key']."], fromUserName[$fromUserName]!");
		Doggy_Log_Helper::warn("Get rev content [".json_encode($revcontent)."]!");
		
		$this->wx_open_id = $fromUserName;
		
		switch($type) {
			case Sher_Core_Util_Wechat::MSGTYPE_TEXT:
				$revcontent = $weObj->getRev()->getRevContent();
				Doggy_Log_Helper::warn("Get wexin type[$type], content[$revcontent]!");
				// 转换为小写
				$content = strtolower($revcontent);
				
				if($content == '太火鸟'){
					$data = $this->newest();
					$result = $weObj->news($data)->reply(array(), true);
				}elseif($content == '智造革命'){
					$text = $this->z();
					$result = $weObj->text($text)->reply(array(), true);
				}
				break;
			case Sher_Core_Util_Wechat::MSGTYPE_EVENT:
				$rev_data = $weObj->getRev()->getRevData();
				$data = $this->handle_event($event, $rev_data);
				if ($event['key'] == 'MENU_KEY_SOCIAL_CONTACT' || $event['key'] == 'MENU_KEY_ORDER'){ // 联系我们，我的订单
					$result = $weObj->text($data)->reply(array(), true);
				} else {
					if ($event['event'] == 'subscribe'){ // 扫描二维码关注, strtolower($event['event']) == 'scan'
						$result = $weObj->text($data)->reply(array(), true);
					} else {
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
	protected function handle_text($content, $rev_data=array()){
		Doggy_Log_Helper::warn("Handle wexin content[$content]!");
		// 转换为小写
		$content = strtolower($content);
		$result = array();
		
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
			case 'MENU_KEY_ORDER':
				$result = $this->myorders();
				break;
		}
		
		Doggy_Log_Helper::warn("Handle event [$event]!");
		$event = strtolower($event);
		// 扫描关注二维码
		switch($event){
			case 'subscribe':
				// $result = $this->handle_subscribe($rev_data);
				$result = $this->welcome();
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
	 * 返回我的订单信息
	 */
	protected function myorders(){
		$default_text = '亲，还没有下单哦，更多精彩快行动吧！';
		if (empty($this->wx_open_id)) {
			return $default_text;
		}
		// 检测是否存在用户
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('wx_open_id'=>$this->wx_open_id));
		// 不存在用户
		if (empty($result)) {
			return $default_text;
		}
		$user_id = $result['_id'];
		
		// 获取订单信息
		$query = array(
			'user_id' => (int)$user_id,
			'status' => array('$in' => array(Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,Sher_Core_Util_Constant::ORDER_READY_GOODS,Sher_Core_Util_Constant::ORDER_SENDED_GOODS))
		);
		$options = array(
			'page' => 1,
			'size' => 1,
			'sort' => array('created_on'=>-1)
		);
		$orders = new Sher_Core_Model_Orders();
		$list = $orders->find($query,$options);
		if (empty($list)){
			return $default_text;
		}
		$extlist = $orders->extended_model_row($list);
		$result_text = '';
		Doggy_Log_Helper::warn("Get orders result [".json_encode($extlist)."]!");
		for($i=0;$i<count($extlist);$i++){
			$result_text .= "订单号：".$extlist[$i]['rid'].",订单金额：￥".$extlist[$i]['pay_money'].",订单状态：".$extlist[$i]['status_label'].".";
		}
		
		return $result_text;
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
		$welcome = '太火鸟 Taihuoniao.com 是中国顶尖的创新产品众包设计平台。更多新鲜，更妙创意，敬请期待！了解更多请回复“太火鸟”。参与活动请回复“智造革命”。';
		
		return $welcome;
	}
	
	/**
	 * 智造革命专题
	 */
	protected function z(){
		$text = '太火鸟已经将您的信息收录喽，中奖名单将于8月13日公布，敬请期待！';
		return $text;
	}
	
	/**
	 * 最新产品列表
	 */
	protected function newest() {
		$result = array(
			"0" => array(
				'Title' => '太火鸟梦想起航',
				'Description'=>'全球首款革命性智能空气净化器活动,现在预订就有机会赢取大奖',
				'PicUrl'=>'http://frbird.qiniudn.com/topic/140810/53e70f85989a6a383e8b4b17-3-hu.jpg',
				'Url'=>'http://www.taihuoniao.com/topic/view-100012-1.html'
			),
			"1" => array(
				'Title' => '太火鸟传奇',
				'Description'=>'太火鸟栖息在海拔2000米左右的高山上，吃天露花蜜，在空中轻盈灵巧的飞舞时，能发出一阵阵迷人的乐声。',
				'PicUrl'=>'http://frbird.qiniudn.com/topic/140809/53e5e1cd989a6a1d078b63fd-1-hu.jpg',
				'Url'=>'http://www.taihuoniao.com/topic/view-100009-1.html'
			),
			"2" => array(
				'Title' => '智造革命 太火鸟&LKK',
				'Description'=>'智造革命活动,参与就有机会赢取大奖',
				'PicUrl'=>'http://frstatic.qiniudn.com/images/case-dm.jpg',
				'Url'=>'http://www.taihuoniao.com/topic/view-100013-1.html'
			),
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
						'type' => 'click',
						'name' => '精选商品',
						"key" => "MENU_KEY_SHOP",
						'sub_button' => array(
							array(
						   	 	"type" => "view",
						   	 	"name" => "智造革命",
								"url" => "http://www.taihuoniao.com/wechat/shop/z"
							)
						)
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
						   	 	"type" => "click",
						   	 	"name" => "我的订单",
								"key" => "MENU_KEY_ORDER"
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