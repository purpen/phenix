<?php

/**
 * 
 * 封装数据
 * @author tianshuai
 *
 */
class Sher_Core_Util_JdPay_ClientOrder
{
    /**
     * 封装数据
     */
    public function execute($data){

		$param;
		$param["version"]=$data["version"];
		$param["merchant"]=$data["merchant"];
		$param["device"]=$data["device"];
		$param["tradeNum"]=$data["tradeNum"];
		$param["tradeName"]=$data["tradeName"];
		$param["tradeDesc"]=$data["tradeDesc"];
		$param["tradeTime"]= $data["tradeTime"];
		$param["amount"]= $data["amount"];
		$param["currency"]= $data["currency"];
		$param["note"]= $data["note"];
		
		$param["callbackUrl"]= $data["callbackUrl"];
		$param["notifyUrl"]= $data["notifyUrl"];
		$param["ip"]= $data["ip"];
		$param["specCardNo"]= $data["specCardNo"];
		$param["specId"]= $data["specId"];
		$param["specName"]= $data["specName"];
		$param["userType"]= $data["userType"];
		$param["userId"]= $data["userId"];
		$param["expireTime"]= $data["expireTime"];
		$param["orderType"]= $data["orderType"];
		$param["industryCategoryCode"]= $data["industryCategoryCode"];
		$unSignKeyList = array ("sign");
		$oriUrl = $data["saveUrl"];
		$desKey = $data["desKey"];
		$sign = Sher_Core_Util_JdPay_SignUtil::signWithoutToHex($param, $unSignKeyList);
		//echo $sign."<br/>";
		$param["sign"] = $sign;
		$keys = base64_decode($desKey);
		
		if($param["device"] != null && $param["device"]!=""){
			$param["device"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["device"]);
		}
		$param["tradeNum"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["tradeNum"]);
		if($param["tradeName"] != null && $param["tradeName"]!=""){
			$param["tradeName"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["tradeName"]);
		}
		if($param["tradeDesc"] != null && $param["tradeDesc"]!=""){
			$param["tradeDesc"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["tradeDesc"]);
		}
		
		$param["tradeTime"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["tradeTime"]);
		$param["amount"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["amount"]);
		$param["currency"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["currency"]);
		$param["callbackUrl"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["callbackUrl"]);
		$param["notifyUrl"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["notifyUrl"]);
		$param["ip"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["ip"]);
		
		
		
		if($param["note"] != null && $param["note"]!=""){
			$param["note"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["note"]);
		}
		if($param["userType"] != null && $param["userType"]!=""){
			$param["userType"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["userType"]);
		}
		if($param["userId"] != null && $param["userId"]!=""){
			$param["userId"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["userId"]);
		}
		if($param["expireTime"] != null && $param["expireTime"]!=""){
			$param["expireTime"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["expireTime"]);
		}
		if($param["orderType"] != null && $param["orderType"]!=""){
			$param["orderType"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["orderType"]);
		}
		if($param["industryCategoryCode"] != null && $param["industryCategoryCode"]!=""){
			$param["industryCategoryCode"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["industryCategoryCode"]);
		}
		if($param["specCardNo"] != null && $param["specCardNo"]!=""){
			$param["specCardNo"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["specCardNo"]);
		}
		if($param["specId"] != null && $param["specId"]!=""){
			$param["specId"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["specId"]);
		}
		if($param["specName"] != null && $param["specName"]!=""){
			$param["specName"]=Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $param["specName"]);
		}

        return array('params'=>$param, 'oriUrl'=>$oriUrl);

    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
	public function buildRequestForm($para_temp, $oriUrl, $method) {
		//待请求参数数组
        $para = $para_temp;
        $sHtml = '';

        $sHtml .= '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="expires" content="0"/>';
        $sHtml .= '<meta http-equiv="pragma" content="no-cache"/><meta http-equiv="cache-control" content="no-cache"/>';
        $sHtml .= '<title>京东支付</title></head><body onload="autosubmit()">';
		$sHtml .= "<form id='jdpaysubmit' name='jdpaysubmit' action='".$oriUrl."' method='".$method."'>";
		while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        //$sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
		// 去除确认按钮
		$sHtml = $sHtml."</form>";
		
		$sHtml = $sHtml."<script>document.forms['jdpaysubmit'].submit();</script>";
		$sHtml = $sHtml."</body></html>";
		
		return $sHtml;
	}
    
}
