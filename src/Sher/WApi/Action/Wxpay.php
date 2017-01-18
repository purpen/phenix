<?php
/**
 * 微信支付相关接口
 * @author tianshuai 
 */
class Sher_WApi_Action_Wxpay extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {

	protected $filter_auth_methods = array('execute', 'notify');
		
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
        require_once "wxpay-xcx-sdk/lib/WxPay.Api.php";
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
        $notify_url = sprintf("%s/wxpay/notify", Doggy_Config::$vars['app.url.wapi']);

		// 统一下单
        $input = new WxPayUnifiedOrder();
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

	/**
	 * 微信支付异步返回通知信息--fiu
	 */
	public function notify(){

        require_once "wxpay-xcx-sdk/lib/WxPay.Api.php";
        require_once 'wxpay-xcx-sdk/lib/WxPay.Notify.php';
        require_once 'wxpay-xcx-sdk/lib/WxPay.PayNotifyCallBack.php';
			
	    // 返回微信支付结果通知信息
        $notify = new PayNotifyCallBack();
		$notify->Handle();
			
        // 获取通知信息
        $notifyInfo = $notify->arr_notify; 
        
        Doggy_Log_Helper::warn("微信小程序获取通知信息~fiu: ".json_encode($notifyInfo));

        // 商户订单号
        $out_trade_no = $notifyInfo['out_trade_no'];
        // 微信交易号
        $trade_no = $notifyInfo['transaction_id'];
        // 交易状态
        $trade_status = $notifyInfo['result_code'];
			
		if($trade_status == 'SUCCESS') {
			if($this->update_order_process($out_trade_no, $trade_no)){
				return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            }else{
    		    Doggy_Log_Helper::warn("微信小程序:订单更新失败~fiu!");        
            }
		}else{
 			Doggy_Log_Helper::warn("微信小程序~fiu:订单交易返回错误: ".json_encode($notifyInfo));       
			return false; 
		}
	}


	/**
	 * 更新订单状态
	 */
	protected function update_order_process($out_trade_no, $trade_no){
		   
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($out_trade_no);
        if (empty($order_info)){
            Doggy_Log_Helper::warn("微信小程序:系统不存在订单!");
            return false;
        }
        $status = $order_info['status'];
        $is_presaled = $order_info['is_presaled'];
        $order_id = (string)$order_info['_id'];
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
       
        Doggy_Log_Helper::warn("Weixin order[$out_trade_no] status[$status] updated!");
       
        // 验证订单是否已经付款
        if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
            // 更新支付状态,付款成功并配货中
            return $model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_WEIXIN, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
        }else{
            return true;
        }
	}



}

