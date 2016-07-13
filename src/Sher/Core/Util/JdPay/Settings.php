<?php

/**
 * 
 * @author tianshuai
 *
 */
class Sher_Core_Util_JdPay_Settings
{
	var $_settings = array ();
	/**
	 * 获取某些设置的值
	 *
	 * @param unknown_type $var        	
	 * @return unknown
	 */
	function get($var) {
		$var = explode ( '.', $var );
		
		$result = $this->_settings;
		foreach ( $var as $key ) {
			if (! isset ( $result [$key] )) {
				return false;
			}
			
			$result = $result [$key];
		}
		
		return $result;
	}
	function load($file) {
		trigger_error ( 'Not yet implemented', E_USER_ERROR );
	}

}
