<?php
/**
 * 公众号开发
 * @author tianshuai
 */
class Sher_Core_Util_WxPub extends Doggy_Object {

	/**
	 * 用SHA1算法生成安全签名
	 * @param string $token 票据
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 * @param string $encrypt 密文消息
	 */
	public static function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
	{

	}

}
