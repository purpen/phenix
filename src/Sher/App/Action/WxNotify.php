<?php

/** 
 * 支付回调通知相关类
 * @ caowei@taihuoniao.com
 */

class Sher_App_Action_WxNotify extends Sher_Core_Util_WxPay_WxPayNotify
{
	// 传入微信订单号查询订单是否付款成功
	public function Queryorder($transaction_id)
	{
		
		$input = new Sher_Core_Util_WxPay_WxPayData_WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = Sher_Core_Util_WxPay_WxPayApi::orderQuery($input);
		
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	// 重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{

        Doggy_Log_Helper::warn("call back:" . json_encode($data));
		
		$notfiyOutput = array();
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		
		// 查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}