<?php
/**
 * 京东开普勒计划
 * @author tianshuai
 */
class Sher_Core_Util_Vop {

    public function __construct() {

    }

    const APP_KEY = "f2dc5b4aefe94658a6ec295be0ec13f2";
    const APP_SECRET = "a045f1cd8b724d05b0f98033e00e7d27";
    const USER_NAME = "bjthhn";
    const PASSWORD = "jd123456";
	
	/**
	 * 获取token
     * { "access_token":"b56dc61ca5db41ab92671da8f65036ab8", "code":"0", "expires_in":2592000, "refresh_token":"afb3e78179c24254a8d4bb5b63ca179c9", "time":"1475919842275", "token_type":"bearer", "uid":"1201453158", "user_nick":"bjthhn" }
	 */
    public static function fetchToken()
    {
        $result = array();
        $result['success'] = true;
        $result['code'] = 0;
        $result['msg'] = null;
        // 先从数据库取
        $dig_model = new Sher_Core_Model_DigList();
        $key_id = Sher_Core_Util_Constant::DIG_JD_VOP_TOKEN;
        $dig = $dig_model->load($key_id);
        // 取token
        $items_arr = array();
        if(!empty($dig) && !empty($dig['items'])){
            $now = time();
            $created_on = isset($dig['items']['created_on']) ? $dig['items']['created_on'] : 0;
            $access_token = $dig['items']['access_token'];
            $expires_in = $dig['items']['expires_in'];
            $refresh_token = $dig['items']['refresh_token'];

            // token大于14天
            if(($now - $created_on) < 2419200){
                $result['data'] = $dig['items'];
                return $result;
            }

        }

        // 重新获取token
        
        //$grant_type = "password";
        $grant_type = "refresh_token";
        $app_key = self::APP_KEY;
        $app_secret = self::APP_SECRET;
        $username = self::USER_NAME;
        $password = md5(self::PASSWORD);
        $state = 0;

        $url = "https://kploauth.jd.com/oauth/token";
        $params = sprintf("grant_type=%s&app_key=%s&app_secret=%s&state=%s&username=%s&password=%s", $grant_type, $app_key, $app_secret, $state, $username, $password);

        $fetch_token = Sher_Core_Helper_Util::request($url, $params);
        $fetch_token = Sher_Core_Helper_Util::object_to_array(json_decode($fetch_token));
        $code = 0;
        if(isset($fetch_token['code'])) $code = (int)$fetch_token['code'];
        if($code==0){
            $row = array(
                'access_token' => $fetch_token['access_token'],
                'expires_in' => $fetch_token['expires_in'],
                'refresh_token' => $fetch_token['refresh_token'],
                'time' => $fetch_token['time'],
                'token_type' => $fetch_token['token_type'],
                'uid' => $fetch_token['uid'],
                'user_nick' => $fetch_token['user_nick'],
                'created_on' => time(),
            );

            // 更新数据
            if(empty($dig)){
                $dig_model->create(array('_id'=>$key_id, 'items'=>$row));
            }else{
                $dig_model->update_set($key_id, array('items'=>$row));
            }
            $result['data'] = $row;
            return $result;
        }else{
            $result['success'] = false;
            $result['code'] = $code;
            $result['msg'] = $fetch_token['desc'];
            return $result;
        }
    
    }

    /**
     * 接口方法封装
     */
    public static function fetchInfo($method, $options=array()){
        $url  ="https://router.jd.com/api";

        $result = array();
        $result['code'] = 0;
        $result['msg'] = '';
        $result['data'] = array();

        $app_key = self::APP_KEY;

        $result_token = Sher_Core_Util_Vop::fetchToken();

        $access_token = isset($result_token['data']['access_token']) ? $result_token['data']['access_token'] : null;
        //$access_token = "2decce31ca234c3d84b921c1b3fc6fbd8";
        $timestamp = date('Y-m-d H:i:s');
        $v = "1.0";
        $format = "json";
        $param_json = $options['param'];
        $response_key = $options['response_key'];

        $params = sprintf("method=%s&app_key=%s&access_token=%s&timestamp=%s&v=%s&format=%s&param_json=%s", $method, $app_key, $access_token, $timestamp, $v, $format, $param_json);

        $data = Sher_Core_Helper_Util::request($url, $params);
        $data = Sher_Core_Helper_Util::object_to_array(json_decode($data));
        //print_r($data);exit;

        if(isset($data['errorResponse'])){
            $result['code'] = (int)$data['errorResponse']['code'];
            $result['msg'] = $data['errorResponse']['msg'];
            return $result;
        }

        if(!isset($data[$response_key])){
            $result['code'] = 5000;
            $result['msg'] = '数据格式不正确!';
            return $result;       
        }

        $obj = $data[$response_key];
        if(!isset($obj['success']) && $obj['success']==0){
            $result['code'] = $obj['code'];
            $result['msg'] = $obj['resultMessage'];
            return $result;
        }
        $result['data'] = $obj;

        return $result;

    }

    /**
     * 商品是否可售(sku_ids 最多不超过100个)
     */
    public static function sku_check($sku_ids, $options=array()){
        $method = 'biz.product.sku.check';
        $response_key = 'biz_product_sku_check_response';
        $is_arr = isset($options['param_arr']) ? true : false;
        if($is_arr){
            $sku_ids = implode(',', $sku_ids);
        }
        $params = array('skuIds'=>$sku_ids);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        return $result;
    }

    /**
     * 商品是否可售/上架(单一)
     */
    public static function sku_check_one($sku_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        //  是否可售
        $method = 'biz.product.sku.check';
        $response_key = 'biz_product_sku_check_response';
        $params = array('skuIds'=>$sku_id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($vop_result['code'])){
            $result['message'] = '接口异常！';
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }
        if($vop_result['data']['result'][0]['saleState'] != 1){
            $result['message'] = 'vop产品不可售！';
            return $result;
        }

        // 是否下架
        $method = 'biz.product.state.query';
        $response_key = 'biz_product_state_query_response';
        $params = array('sku'=>$sku_id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($vop_result['code'])){
            $result['message'] = '接口异常！';
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }
        if($vop_result['data']['result'][0]['state'] != 1){
            $result['message'] = 'vop产品已下架！';
            return $result;
        }

        $result['success'] = true;
        return $result;
    }

    /**
     * 验证商品支持当前地区购买(单一)
     */
    public static function sku_check_area($sku_id, $options=array()){
        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        // 是否支持此地区发货
        $method = 'biz.product.checkAreaLimit.query';
        $response_key = 'biz_product_checkAreaLimit_query_response';
        $province = $options['province'];
        $city = $options['city'];
        $county = $options['county'];
        $town = $options['town'];
        $params = array('skuIds'=>$sku_id, 'province'=>$province, 'city'=>$city, 'county'=>$county, 'town'=>$town);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = '接口异常！';
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }
        if($vop_result['data']['result'][0]['isAreaRestrict']==true){
            $title = isset($options['title']) ? $options['title'] : null;
            $result['message'] = sprintf('商品[%s]不支持该地区发货！', $title);
            return $result;
        }

        $result['success'] = true;
        return $result;   
    
    }

    /**
     * 创建订单
     */
    public static function create_order($rid, $options=array()){
        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        if(empty($rid)){
            $result['message'] = '缺少请求参数!';
            return $result;
        }

        $order = $options['data'];
        $addbooks_model = new Sher_Core_Model_DeliveryAddress();
        $addbook = $addbooks_model->load($order['addbook_id']);
        if(empty($addbook)){
            $result['message'] = '收货地址不存在!';
            return $result;
        }

        $order['express_info'] = array();

        $order['express_info']['name'] = $addbook['name'];
        $order['express_info']['phone'] = $addbook['phone'];
        $order['express_info']['province_id'] = $addbook['province_id'];
        $order['express_info']['city_id'] = $addbook['city_id'];
        $order['express_info']['county_id'] = $addbook['county_id'];
        $order['express_info']['town_id'] = $addbook['town_id'];
        $order['express_info']['address'] = $addbook['address'];
        $order['express_info']['zip'] = $addbook['zip'];
        $order['express_info']['email'] = !empty($addbook['email']) ? $addbook['email'] : 'tianshuai@taihuoniao.com';

        $data = array();
        $items = array();
        $skus = array();
        for($i=0;$i<count($order['items']);$i++){
            $item = array();
            $sku = array();
            $quantity = $order['items'][$i]['quantity'];
            $vop_id = $order['items'][$i]['vop_id'];
            $sale_price = $order['items'][$i]['sale_price'];
            $item['skuId'] = $vop_id;
            $item['num'] = $quantity;
            // 是否需要附件
            $item['bNeedAnnex'] = true;
            // 是否需要赠品
            $item['bNeedGift'] = true;
            array_push($items, $item);
            $sku["$vop_id"] = $sale_price*$quantity;
            array_push($skus, $sku);
        }

        $data['thirdOrder'] = (string)$rid;
        $data['sku'] = $items;

        // 收货信息
        $data['name'] = $order['express_info']['name'];
        $data['mobile'] = $order['express_info']['phone'];
        $data['province'] = $order['express_info']['province_id'];
        $data['city'] = $order['express_info']['city_id'];
        $data['county'] = $order['express_info']['county_id'];
        $data['town'] = $order['express_info']['town_id'];
        $data['address'] = $order['express_info']['address'];
        $data['zip'] = $order['express_info']['zip'];
        $data['email'] = $order['express_info']['email'];

        $data['remark'] = $order['summary'];
        // 开票方式: 0.订单预借；1.随货开票；2.集中开票；
        $data['invoiceState'] = 2;
        // 发货类型：1.普通发票；2.增值发票；
        $data['invoiceType'] = 1;
        // 4。个人；5。单位；
        $data['selectedInvoiceTitle'] = 5;
        $data['companyName'] = '北京太火红鸟科技有限公司';
        // 1:明细，3：电脑配件，19:耗材，22：办公用品
        $data['invoiceContent'] = 1;
        // 1：货到付款，2：邮局付款，4：在线支付（余额支付），5：公司转账，6：银行转账，7：网银钱包， 101：金采支付
        $data['paymentType'] = 4;
        // 预存款【即在线支付（余额支付）】下单固定1 使用余额
        $data['isUseBalance'] = 1;
        // 是否预占库存，0是预占库存（需要调用确认订单接口），1是不预占库存
        $data['submitState'] = 0;
        // 增值票收票人姓名
        //$data['invoiceName'] = '';
        // 增值票收票人电话
        //$data['invoicePhone'] = '';
        // 增值票收票人所在省(京东地址编码)
        //$data['invoiceProvice'] = '';
        // 增值票收票人所在市(京东地址编码)
        //$data['invoiceCity'] = '';
        // 增值票收票人所在区/县(京东地址编码)
        //$data['invoiceCounty'] = '';
        // 增值票收票人所在地址
        //$data['invoiceAddress'] = '';
        // 下单价格模式: 0: 客户端订单价格快照不做验证对比，还是以京东端价格正常下单;1:必需验证客户端订单价格快照，如果快照与京东端价格不一致返回下单失败，需要更新商品价格后，重新下单;
        $data['doOrderPriceMode'] = 0;
        /** 买断模式下，该参数必传:   
         * paymentType:是否由京东代收货款：1：是；0：否;
         * mobile:C用户手机号码;
         * skus: 如："{"107810":"50.30","181818":"22.30"}"    107810：京东SkuId---100.60：商品在C客户价格*数量
         * C用户在客户系统下单的订单金额。
         */

        $data['extContent'] = array(
            'paymentType' => 0,
            'mobile' => '13621363406',
            'skus' => $skus,
            'orderPrice' => $order['pay_money'],
        );

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        // 下单接口
        $method = 'biz.order.unite.submit';
        $response_key = 'biz_order_unite_submit_response';
        $params = $data;
        $json = !empty($params) ? json_encode($params) : '{}';
        //return $json;
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        //return $vop_result;

        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            $result['code'] = $vop_result['code'];
            return $result;
        }

        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            $result['code'] = $vop_result['data']['resultCode'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data']['result'];
        return $result;
    
    }

    /**
     * 确认预占库存订单（用户已支付后调用此接口）
     */
    public static function sure_order($jd_order_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        // 确认预占库存接口(已付款订单)
        $method = 'biz.order.occupyStock.confirm';
        $response_key = 'biz_order_occupyStock_confirm_response';

        $params = array('jdOrderId'=>$jd_order_id);
        $json = !empty($params) ? json_encode($params) : '{}';
        //return $json;
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            $result['code'] = $vop_result['code'];
            return $result;
        }

        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            $result['code'] = $vop_result['data']['resultCode'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data'];
        return $result;
    
    }

    /**
     * 发起支付
     * 已确认预占库存订单不需要调用此接口，只有长时间未收到拆单信息才调用此接口
     */
    public static function pay($jd_order_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        $method = 'biz.order.doPay';
        $response_key = 'biz_order_doPay_response';

        $params = array('jdOrderId'=>$jd_order_id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['result'];
        return $result;
    
    }


    /**
     * 取消订单-- 无效，不支持已占库存订单
     */
    public static function cancel_order($jd_order_id, $options=array()){

        return array('success'=>true, 'message'=>'undefault', 'data'=>array());

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        $method = 'biz.order.cancelorder';
        $response_key = 'biz_order_cancelorder_response';

        $params = array('jdOrderId'=>$jd_order_id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['result'];
        return $result;
    
    }


    /**
     * 验证订单是否支持售后
     */
    public static function check_after_sale($jd_order_id, $sku_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        $method = 'biz.afterSale.availableNumberComp.query';
        $response_key = 'biz_afterSale_availableNumberComp_query_response';
        
        $params = array('param'=>array('jdOrderId'=>$jd_order_id, 'skuId'=>$sku_id));
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data']['result'];
        return $result;
    }

    /**
     * 验证订单支持服务类型(退货、换货、返修)
     */
    public static function check_after_sale_customer($jd_order_id, $sku_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        $method = 'biz.afterSale.customerExpectComp.query';
        $response_key = 'biz_afterSale_customerExpectComp_get_response';
        
        $params = array('param'=>array('jdOrderId'=>$jd_order_id, 'skuId'=>$sku_id));
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data']['result'];
        return $result;
    }

    /**
     * 验证订单支持商品返回京东方式(上门取件、客户发货、客户送货)
     */
    public static function check_after_sale_return($jd_order_id, $sku_id, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        $method = 'biz.afterSale.wareReturnJdComp.query';
        $response_key = 'biz_afterSale_wareReturnJdComp_query_response';
        
        $params = array('param'=>array('jdOrderId'=>$jd_order_id, 'skuId'=>$sku_id));
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = $vop_result['msg'];
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data']['result'];
        return $result;
    }


    /**
     * 通过地址自动获取邮费
     */
    public static function fetch_freight($skus, $paymentType, $options=array()){
        $result = array();
        $result['success'] = false;
        $result['message'] = 'success';

        // 获取邮费
        $method = 'biz.order.freight.get';
        $response_key = 'biz_order_freight_get_response';
        $province = $options['province'];
        $city = $options['city'];
        $county = $options['county'];
        $town = $options['town'];
        $params = array('sku'=>$skus, 'province'=>$province, 'city'=>$city, 'county'=>$county, 'town'=>$town, 'paymentType'=>$paymentType);
        $json = !empty($params) ? json_encode($params) : '{}';
        $vop_result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));
        if(!empty($vop_result['code'])){
            $result['message'] = '接口异常！';
            return $result;
        }
        if(empty($vop_result['data']['success'])){
            $result['message'] = $vop_result['data']['resultMessage'];
            return $result;
        }

        $result['success'] = true;
        $result['data'] = $vop_result['data']['result'];
        return $result;   
    
    }

	
}

