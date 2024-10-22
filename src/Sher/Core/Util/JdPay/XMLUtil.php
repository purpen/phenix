<?php

/**
 * 
 * 
 * @author wylitu
 *
 */
class Sher_Core_Util_JdPay_XMLUtil
{
	public static function arrtoxml($arr,$dom=0,$item=0){
		//ksort($arr);
		if (!$dom){
				
			$dom = new \DOMDocument("1.0","UTF-8");
		}
		if(!$item){
			$item = $dom->createElement("jdpay");
			$item = $dom->appendChild($item);
		}
		foreach ($arr as $key=>$val){
			$itemx = $dom->createElement(is_string($key)?$key:"item");
			$itemx = $item->appendChild($itemx);
			if (!is_array($val)){
				$text = $dom->createTextNode($val);
				$text = $itemx->appendChild($text);
				 
			}else {
				Sher_Core_Util_JdPay_XMLUtil::arrtoxml($val,$dom,$itemx);
			}
		}
		return $dom;
	}
	
	public static function xmlToString($dom){
		$xmlStr = $dom->saveXML();
		$xmlStr = str_replace("\r", "", $xmlStr);
		$xmlStr = str_replace("\n", "", $xmlStr);
		$xmlStr = str_replace("\t", "", $xmlStr);
		$xmlStr = preg_replace("/>\s+</", "><", $xmlStr);
		$xmlStr = preg_replace("/\s+\/>/", "/>", $xmlStr);
		$xmlStr = str_replace("=utf-8", "=UTF-8", $xmlStr);
		return $xmlStr;
	}
	
	public static function encryptReqXml($param){
		$dom = Sher_Core_Util_JdPay_XMLUtil::arrtoxml($param);
		$xmlStr = Sher_Core_Util_JdPay_XMLUtil::xmlToString($dom);
		//echo "源串：".htmlspecialchars($xmlStr)."<br/>";
		$sha256SourceSignString = hash("sha256", $xmlStr);
		//echo "摘要:".$sha256SourceSignString."<br/>";
		$sign = Sher_Core_Util_JdPay_ConfigUtil::encryptByPrivateKey($sha256SourceSignString);
		$rootDom = $dom->getElementsByTagName("jdpay");
		$signDom = $dom->createElement("sign");
		$signDom = $rootDom->item(0)->appendChild($signDom);
		$signText = $dom->createTextNode($sign);
		$signText = $signDom->appendChild($signText);
		$data = Sher_Core_Util_JdPay_XMLUtil::xmlToString($dom);
		//echo "封装后:".htmlspecialchars($data)."<br/>";
		
		$desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];
		$keys = base64_decode($desKey);
		$encrypt = Sher_Core_Util_JdPay_TDESUtil::encrypt2HexStr($keys, $data);
		//echo "3DES后:".$encrypt."<br/>";
		$encrypt = base64_encode($encrypt);
		//echo "base64后:".$encrypt."<br/>";
		$reqParam;
		$reqParam["version"]=$param["version"];
		$reqParam["merchant"]=$param["merchant"];
		$reqParam["encrypt"]=$encrypt;
		$reqDom = Sher_Core_Util_JdPay_XMLUtil::arrtoxml($reqParam,0,0);
		$reqXmlStr = Sher_Core_Util_JdPay_XMLUtil::xmlToString($reqDom);
		//echo htmlspecialchars($reqXmlStr)."<br/>";
		return $reqXmlStr;
	}
	
	public static function decryptResXml($resultData,&$resData){
		$resultXml = simplexml_load_string($resultData);
		$resultObj = json_decode(json_encode($resultXml),TRUE);
		$encryptStr = $resultObj["encrypt"];
		$encryptStr=base64_decode($encryptStr);
		$desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];
		$keys = base64_decode($desKey);
		$reqBody = Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $encryptStr);
		//echo "请求返回encrypt Des解密后:".$reqBody."\n";
		
		$bodyXml = simplexml_load_string($reqBody);
		$resData = json_decode(json_encode($bodyXml),TRUE);
		
		$inputSign = $resData["sign"];
// 		$bodyDom = XMLUtil::arrtoxml($bodyObj,0,0);
// 		$rootDom = $bodyDom->getElementsByTagName("jdpay");
// 		$signNodelist = $rootDom[0]->getElementsByTagName("sign");
// 		$rootDom[0]->removeChild($signNodelist[0]);
		
// 		$reqBodyStr = XMLUtil::xmlToString($bodyDom);

		$startIndex = strpos($reqBody,"<sign>");
		$endIndex = strpos($reqBody,"</sign>");
		$xml;
		
		if($startIndex!=false && $endIndex!=false){
			$xmls = substr($reqBody, 0,$startIndex);
			$xmle = substr($reqBody,$endIndex+7,strlen($reqBody));
			$xml=$xmls.$xmle;
		}
		
		//echo "本地摘要原串:".$xml."\n";
		$sha256SourceSignString = hash("sha256", $xml);
		//echo "本地摘要:".$sha256SourceSignString."\n";
		
		$decryptStr = Sher_Core_Util_JdPay_ConfigUtil::decryptByPublicKey($inputSign);
		//echo "解密后摘要:".$decryptStr."<br/>";
		$flag;
		if($decryptStr==$sha256SourceSignString){
			//echo "验签成功<br/>";
			$flag=true;
		}else{
			//echo "验签失败<br/>";
			$flag=false;
		}
		$resData["version"]=$resultObj["version"];
		$resData["merchant"]=$resultObj["merchant"];
		$resData["result"]=$resultObj["result"];
		//echo var_dump($resData);
		return $flag;
	}

}
