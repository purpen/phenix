<?php
/**
 * 快递鸟
 * 
 * @author tianshuai
 * @version 1.0
 */ 
class Sher_Core_Util_Kdniao extends Doggy_Object {


    // 配置
    const EB_ID = 1266663;
    const APP_KEY = '878b185d-df1a-4e50-bc99-7f6dc1d1d0ac';
    const REQ_URL = 'http://api.kdniao.cc/api/dist';
     
    /**
     * Json方式  物流信息订阅
     */
    public static function orderTracesSubByJson($LogisticCode, $ShipperCode, $OrderCode=null){

        $requestData = json_encode(array('LogisticCode'=> $LogisticCode, 'ShipperCode'=>$ShipperCode, 'OrderCode'=>$OrderCode));
        
        $datas = array(
            'EBusinessID' => self::EB_ID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = self::encrypt($requestData, self::APP_KEY);
        $result = self::sendPost(self::REQ_URL, $datas);
        $result = Sher_Core_Helper_Util::object_to_array(json_decode($result));
        
        //根据公司业务处理返回的信息......
        
        return $result;
    }

    /**
     *  post提交数据 
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据 
     * @return url响应返回的html
     */
    public static function sendPost($url, $datas) {
        $temps = array();	
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);		
        }	
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;	
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);  
        
        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容   
     * @param appkey Appkey
     * @return DataSign签名
     */
    public static function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }

    /**
     * 快递公司编码转换
     */
    public static function express_change($express_caty){

        $caty = '';
        switch($express_caty){
            // 申通
            case 's':
            $caty = 'STO';
            break;
            //圆通
            case 'y':
            $caty = 'YTO';
            break;
            // 顺丰
            case 'f':
            $caty = 'SF';
            break;
            // 中通
            case 'z':
            $caty = 'ZTO';
            break;
            // 优速
            case 'u':
            $caty = 'UC';
            break;
            // 韵达
            case 'm':
            $caty = 'YD';
            break;
            // 天天
            case 't':
            $caty = 'HHTT';
            break;
            // 宅急送
            case 'j':
            $caty = 'ZJS';
            break;
            // 百世汇通
            case 'b':
            $caty = 'BTWL';
            break;
            // 国通快递
            case 'g':
            $caty = 'GTO';
            break;
            // EMS
            case 'e':
            $caty = 'EMS';
            break;
            // 德邦物流
            case 'd':
            $caty = 'DBL';
            break;
            // 全峰快递
            case 'q':
            $caty = 'QFKD';
            break;
            // 快捷快递
            case 'k':
            $caty = 'AJ';
            break;

            // 京东快递
            case 'jd':
            $caty = '';
            break;

        }
        return $caty;
    }
	
}

