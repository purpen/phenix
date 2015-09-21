<?php
    
    /**
     *
     * 微信红包开发接口
     * @caowei caowei@taihuoniao.com
     * time:2015-9-17
     *
    */
    class Sher_Core_Util_WechatRedEnvelope extends Doggy_Object {
        
        public $parameters = array(); // 微信红包接口参数
        
        public function __construct($options){
            
            // 实例化对象初始化函数
            $this->token = isset($options['token']) ? $options['token'] : '';
            $this->appid = isset($options['appid']) ? $options['appid'] : '';
            $this->appsecret = isset($options['appsecret']) ? $options['appsecret'] : '';
            $this->partnerid = isset($options['partnerid']) ? $options['partnerid'] : '';
            $this->partnerkey = isset($options['partnerkey']) ? $options['partnerkey'] : '';
            $this->paysignkey = isset($options['paysignkey']) ? $options['paysignkey'] : '';
            $this->key = isset($options['key']) ? $options['key'] : '';
            $this->apiclient_cert = isset($options['apiclient_cert']) ? $options['apiclient_cert'] : '';
            $this->apiclient_key = isset($options['apiclient_key']) ? $options['apiclient_key'] : '';
            $this->rootca = isset($options['rootca']) ? $options['rootca'] : '';
        }
        
        /**
        * 微信红包发送接口
        * 
        * @param string $openid 用户openid
        */
        public function payRedEnvelope($re_openid)
        {
            Doggy_Log_Helper::warn("我是".$re_openid.", 我在微信红包接口类里!");
            $this->parameters['nonce_str'] = $this->set_rand(32,true); // 随机字符串，不长于32位
            $this->parameters['mch_billno'] = $this->set_billno($this->partnerid); // 订单号
            $this->parameters['mch_id'] = $this->partnerid; // 商户号
            $this->parameters['wxappid'] = $this->appid; // 公众账号appid
            $this->parameters['send_name'] = '太火鸟智能馆'; // 商户名称
            $this->parameters['re_openid'] = $re_openid; // openid
            $this->parameters['total_amount'] = 100; // 付款金额，单位分
            $this->parameters['total_num'] = 1; // 红包収放总人数
            $this->parameters['client_ip'] = '127.0.0.1'; // 商家服务器ip地址
            $this->parameters['wishing'] = '感谢您参加猜灯谜活动，祝您生活愉快！'; // 红包祝福语
            $this->parameters['act_name'] = '红包活动'; // 商家活劢名称
            $this->parameters['remark'] = '快来抢！'; // 备注信息
            
            $postXml = $this->create_xml();
            Doggy_Log_Helper::warn($postXml);
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
            $responseXml = $this->curl_post_ssl($url, $postXml);
            Doggy_Log_Helper::warn($responseXml);
            $responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            // 判断是否发送红包成功
            if($responseObj->return_code == 'SUCCESS' && $responseObj->result_code == 'SUCCESS'){
                $arr = $this->object_to_array($responseObj);
                Doggy_Log_Helper::warn('result:'.json_encode($arr));
                return true;
            }
            
            return false;
        }
        
        /**
        *  检测微信红包接口参数是否合法
        */
        protected function check_sign_parameters(){
            if($this->parameters["nonce_str"] == null ||
                $this->parameters["mch_billno"] == null ||
                $this->parameters["mch_id"] == null ||
                $this->parameters["wxappid"] == null ||
                $this->parameters["send_name"] == null ||
                $this->parameters["re_openid"] == null ||
                $this->parameters["total_amount"] == null ||
                $this->parameters["total_num"] == null ||
                $this->parameters["wishing"] == null ||
                $this->parameters["client_ip"] == null ||
                $this->parameters["act_name"] == null ||
                $this->parameters["remark"] == null
                )
            {
                return false;
            }
            return true;
        }
        
        /**
        * 生成红包接口XML信息
        */
        protected function create_xml(){
            try {
                
                $this->parameters['sign'] =  $this->get_sign(); // 签名字符串,长度32位
                Doggy_Log_Helper::warn(json_encode($this->parameters));
                return  $this->arrayToXml($this->parameters);
            
            }catch (Sher_Core_Model_Exception $e)
            {
                Doggy_Log_Helper::warn($e->errorMessage());
            }
        }
        
        /**
         * 获取签名
         */
        protected function get_sign(){
            try {
                if (null == $this->key || "" == $this->key ) {
                    throw new Exception("密钥不能为空！" . "<br>");
                }
                if($this->check_sign_parameters() == false) { // 检查生成签名参数
                   throw new Exception("生成签名参数缺失！" . "<br>");
                }
                ksort($this->parameters);
                $unSignParaString = $this->formatQueryParaMap($this->parameters, false);
                return $this->sign($unSignParaString,$this->trimString($this->key));
            }catch (Exception $e)
            {
                Doggy_Log_Helper::warn($e->errorMessage());
            }
        }
        
        /**
        * formatQueryParaMap 拼接字符串
        * 格式:appid=wxd930ea5d5a258f4f&body=test&device_info=1000&mch_id=10000100&nonce_str=ibuaiVcKdpRxkhJA
        * @param value
        */
        protected function formatQueryParaMap($paraMap, $urlencode){
            $buff = "";
            ksort($paraMap);
            foreach ($paraMap as $k => $v){
                if (null != $v && "null" != $v && "sign" != $k) {
                    if($urlencode){
                       $v = urlencode($v);
                    }
                    $buff .= $k . "=" . $v . "&";
                }
            }
            $reqPar;
            if (strlen($buff) > 0) {
                $reqPar = substr($buff, 0, strlen($buff)-1);
            }
            return $reqPar;
        }
        
        /**
        * 生成签名
        */
        protected function sign($content, $key) {
            try {
                if (null == $key) {
                   throw new Exception("签名key不能为空！" . "<br>");
                }
                if (null == $content) {
                   throw new Exception("签名内容不能为空" . "<br>");
                }
                $signStr = $content . "&key=" . $key;
                return strtoupper(md5($signStr));
            
            }catch (Sher_Core_Model_Exception $e)
            {
                Doggy_Log_Helper::warn($e->errorMessage());
            }
        }
        
        /**
        * 微信商户订单号 - 最长28位字符串
        */
        protected function set_billno($mchid = NULL) {
            
            if(!$mchid){
                return false;
            }
            
            $data = $mchid.date('Ymd',time()).mt_rand(1000000000,9999999999);
            return $data;
        }
        
        /**
        * 生成随机数
        */
        protected function set_rand($length = 16, $type = FALSE) {
            $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $str = "";
            for ($i = 0; $i < $length; $i++) {
                $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            }
            if($type == TRUE){
                return strtoupper(md5(time() . $str));
            }
            else {
                return $str;
            }
        }
        
        /**
        * 将数组转化成xml格式
        */
        protected function arrayToXml($arr)
        {
            $xml = "<xml>";
            foreach ($arr as $key=>$val)
            {
                if (is_numeric($val))
                {
                   $xml.="<".$key.">".$val."</".$key.">"; 
                }
                else{
                   $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
                } 
            }
            $xml.="</xml>";
            return $xml; 
        }
        
        /**
        * trim 
        * @param value
        */
        protected function trimString($value){
            $ret = null;
            if (null != $value) {
                $ret = $value;
                if (strlen($ret) == 0) {
                    $ret = null;
                }
            }
            return $ret;
        }
        
        /**
        * 带有证书的接口访问方法
        */
        protected function curl_post_ssl($url, $vars, $second=30, $aHeader=array())
        {
            $ch = curl_init();
            //超时时间
            curl_setopt($ch,CURLOPT_TIMEOUT,$second);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            //这里设置代理，如果有的话
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);	
            
            //cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERT,$this->apiclient_cert);
            curl_setopt($ch,CURLOPT_SSLKEY,$this->apiclient_key);
            curl_setopt($ch,CURLOPT_CAINFO,$this->rootca);
         
            if( count($aHeader) >= 1 ){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
            }
         
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
            $data = curl_exec($ch);
            if($data){
                curl_close($ch);
                return $data;
            }
            else { 
                $error = curl_errno($ch);
                curl_close($ch);
                return false;
            }
        }
        
        /**
         * 对象转关联数组
         * @author
         * @param object $obj
         * @return array
         */
        protected function object_to_array($obj)
        {
            $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
            foreach ($_arr as $key => $val)
            {
                $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
                $arr[$key] = $val;
            }
            return $arr;
        } 
    }