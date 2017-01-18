<?php
/**
 * 微信支付相关接口
 * @author tianshuai 
 */
class Sher_WApi_Action_Wxpay extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {
		
	// 配置微信参数
	public $options = array();
		
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
     * Fiu 支付流程
     */
    public function payment(){
        require_once "wxpay-sdk/lib/WxPay.Api.php";
        //require_once 'log.php';
        //

        $user_id = $this->uid;

		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $open_id = isset($this->stash['open_id']) ? $this->stash['open_id'] : null;
		if (empty($rid) && empty($open_id)){
			return $this->wapi_json('操作不当，订单号丢失！', 3001);
		}

        $ip = Sher_Core_Helper_Auth::get_ip();
		if (empty($ip)){
			return $this->wapi_json('终端IP为空！', 3004);
		}
			
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($rid);
        if (empty($order_info)){
            return $this->wapi_json('抱歉，系统不存在该订单！', 3005);
        }
        $status = $order_info['status'];

        if($order_info['user_id'] != $user_id){
            return $this->wapi_json('没有权限！', 3006);       
        }
        
        // 验证订单是否已经付款
        if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
            return $this->wapi_json(sprintf("订单[%s]已付款！", $rid), 3007);
        }
        
        // 支付完成通知回调接口
        $notify_url = sprintf("%s/wxpay/fiu_notify", Doggy_Config::$vars['app.url.api']);

		// 统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetAppid(Doggy_Config::$vars['app.wechat.xcx']['app_id']);
        $input->SetMch_id(Doggy_Config::$vars['app.wechat.xcx']['mch_id']);
        $input->SetBody('太火鸟商城'.$order_info['rid'].'的订单');
        $input->SetAttach("2"); // 附加信息，数据原样返回 2.表示Fiu
        $input->SetOut_trade_no($order_info['rid']);
        $input->SetTotal_fee((float)$order_info['pay_money']*100);
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));
        //$input->SetGoods_tag("test"); // 商品标记
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("JSAPI");
        //$input->SetDevice_info(); // 终端设备号
        $input->SetSpbill_create_ip($ip); // 终端IP
        $input->SetOpenid($open_id);
        $order = WxPayApi::unifiedOrder($input);

        if(!empty($order)){
            if($order['return_code'] == 'SUCCESS'){
                if($order['result_code'] == 'SUCCESS'){
                    // 根据prepay_id再次签名
                    if($order['prepay_id']){
                        $order['partner_id'] = $order['mch_id'];
                        $order['time_stamp'] = time();
                        //签名步骤一：按字典序排序参数
                        $val = array(
                            'appid' => Doggy_Config::$vars['app.wechat.xcx']['app_id'],
                            'partnerid' => Doggy_Config::$vars['app.wechat.xcx']['mch_id'],
                            'prepayid' => $order['prepay_id'],
                            'noncestr' => $order['nonce_str'],
                            'timestamp' => $order['time_stamp'],
                            'package' => 'Sign=WXPay',
                        );
                        ksort($val);

                        $buff = "";
                        foreach ($val as $k => $v)
                        {
                            if($k != "sign" && $v != "" && !is_array($v)){
                                $buff .= $k . "=" . $v . "&";
                            }
                        }
                        $string = trim($buff, "&");
                        //签名步骤二：在string后加入KEY
                        $string = $string . "&key=".Doggy_Config::$vars['app.wechat.xcx']['key'];   

                        //签名步骤三：MD5加密
                        $string = md5($string);
                        //签名步骤四：所有字符转为大写
                        $new_sign = strtoupper($string);
                        $order['sign'] = $new_sign;
              
                    }
            
                    return $this->wapi_json('请求成功!', 0, $order);         
                }else{
                    return $this->wapi_json('请求失败!', 3010, $order);          
                }
            }else{
            return $this->wapi_json('请求失败!', 3011, $order);        
            }
        }else{
            $this->wapi_json('请求异常!', 3012);
        }
    
    }



}

