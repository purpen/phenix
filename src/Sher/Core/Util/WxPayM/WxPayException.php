<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class Sher_Core_Util_WxPayM_WxPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
